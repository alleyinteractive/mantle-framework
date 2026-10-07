<?php
namespace Mantle\Tests\Queue;

use Carbon\Carbon;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Job;
use Mantle\Queue\Database_Scheduler;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Events\Job_Failed;
use Mantle\Queue\Jobs\Database_Job_Record;
use Mantle\Queue\Jobs\Status;
use Mantle\Queue\Queueable;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

use function Mantle\Queue\dispatch;

/**
 * WordPress Queue Provider Test
 *
 * @group queue
 * @group wordpress-queue
 */
#[Group( 'queue' )]
#[Group( 'wordpress-queue' )]
class DatabaseQueueTest extends FrameworkTestCase {
	use Refresh_Database;

	public function test_cron_action() {
		$this->assertTrue( has_action( Database_Scheduler::EVENT ) );
	}

	public function test_job_dispatch() {
		$_SERVER['__example_job'] = false;

		$this->assertDatabaseDoesNotHave( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::PENDING->value ] );
		$this->assertJobNotQueued( Example_Job::class );
		$this->assertJobNotQueued( Example_Job::class, queue: 'default' );
		$this->assertNotInCronQueue( Example_Job::class );

		Example_Job::dispatch();

		$this->assertDatabaseHas( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::PENDING->value ], 1 );
		$this->assertInCronQueue( Example_Job::class );
		$this->assertFalse( $_SERVER['__example_job'] );

		// Force the cron to be dispatched which will execute the queued job.
		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__example_job'] );

		// Ensure that the queued job post was deleted.
		$this->assertDatabaseDoesNotHave( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::PENDING->value ] );
		$this->assertDatabaseHas( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::COMPLETED->value ] );
	}

	public function test_reclaims_expired_running_job() {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		// Simulate a worker that claimed the job and then crashed: mark it running
		// with a lock timestamp in the past.
		$record = Database_Job_Record::query()
			->where( 'status', Status::PENDING->value )
			->first();

		$this->assertNotNull( $record );

		$record->save( [
			'status'           => Status::RUNNING->value,
			'available_at_gmt' => now()->subMinutes( 15 )->toDateTimeString(),
		] );

		// The expired lock should allow the job to be reclaimed and run.
		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertDatabaseHas( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::COMPLETED->value ] );
	}

	public function test_does_not_reclaim_locked_running_job() {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		// A running job whose lock is still in the future must not be picked up.
		$record = Database_Job_Record::query()
			->where( 'status', Status::PENDING->value )
			->first();

		$this->assertNotNull( $record );

		$record->save( [
			'status'           => Status::RUNNING->value,
			'available_at_gmt' => now()->addMinutes( 15 )->toDateTimeString(),
		] );

		$this->dispatch_queue();

		$this->assertFalse( $_SERVER['__example_job'] );
		$this->assertDatabaseHas( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::RUNNING->value ] );
	}

	public function test_job_dispatch_now() {
		$this->assertNotInCronQueue( Example_Job::class );

		$_SERVER['__example_job'] = false;

		Example_Job::dispatch_now( false );

		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertJobNotQueued( Example_Job::class, [ false ] );
		$this->assertDatabaseDoesNotHave( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::PENDING->value ] );
	}

	public function test_job_failure() {
		$_SERVER['__failed_run'] = 0;

		$this->app['events']->listen( Job_Failed::class, fn () => $_SERVER['__failed_run']++ );

		$this->assertDatabaseDoesNotHave(
			'mantle_queue',
			[
				'queue' => 'default',
				'status' => Status::PENDING->value,
			],
		);

		Job_To_Fail::dispatch();

		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::PENDING->value,
		], 1 );

		$this->dispatch_queue();

		$this->assertDatabaseDoesNotHave( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::PENDING->value ] );
		$this->assertEquals( 2, $_SERVER['__failed_run'] );

		// Get the last database job record and ensure it has the correct date set.
		$record = Database_Job_Record::query()
			->where( 'status', Status::FAILED->value )
			->orderBy( 'id', 'desc' )
			->first();

		$this->assertNotNull( $record );
		$this->assertNotNull( $record->last_attempt_gmt );
	}

	public function test_job_fail_and_retry(): void {
		$_SERVER['__failed_run'] = 0;

		Job_To_Fail_Retry::dispatch();

		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::PENDING->value,
		], 1 );

		$this->dispatch_queue();

		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::PENDING->value,
			'attempts' => 1,
		], 1 );

		$this->assertEquals( 1, $_SERVER['__failed_run'] );

		// Check if the available_at_gmt is set to the retry backoff time.
		$record = Database_Job_Record::query()
			->where( 'status', Status::PENDING->value )
			->orderBy( 'id', 'desc' )
			->first();

		$this->assertNotNull( $record );
		$this->assertTrue( now()->isBefore( Carbon::parse( $record->available_at_gmt ) ) );

		// Run it through again to ensure it retries.
		$record->save( [
			'available_at_gmt' => now()->toDateTimeString(),
		] );

		$this->dispatch_queue();

		// Refresh the record to ensure we have the latest data.
		$record = $record->refresh();

		$this->assertEquals( 2, $record->attempts );

		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::PENDING->value,
			'attempts' => 2,
		], 1 );

		$this->assertEquals( 2, $_SERVER['__failed_run'] );

		// Run it once more to ensure it fails finally (max of 3 attempts).
		$record->refresh()->save( [
			'available_at_gmt' => now()->toDateTimeString(),
		] );

		$this->dispatch_queue();

		// Refresh the record to ensure we have the latest data.
		$record = $record->refresh();

		$this->assertEquals( 3, $record->attempts );

		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::FAILED->value,
			'attempts' => 3,
		], 1 );

		$this->assertEquals( 3, $_SERVER['__failed_run'] );
	}

	public function test_job_dispatch_anonymous() {
		$_SERVER['__closure_job'] = false;

		dispatch( function() {
			$_SERVER['__closure_job'] = true;
		} );

		// Assert the serialize queue post exists.
		// TODO: Add check for the serialized closure.
		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::PENDING->value,
		] );

		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__closure_job'] );

		$this->assertDatabaseDoesNotHave( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::PENDING->value ] );
	}

	public function test_job_dispatch_anonymous_failure() {
		$_SERVER['__closure_job'] = false;
		$_SERVER['__failed_run']  = false;

		$this->app['events']->listen(
			Job_Failed::class,
			fn () => $_SERVER['__failed_run'] = true,
		);

		dispatch(
			fn () => throw new RuntimeException( 'Something went wrong' ),
		)->catch( fn () => $_SERVER['__failed_run'] = true );

		// Assert the serialize queue post exists.
		// TODO: Add check for the serialized closure.
		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::PENDING->value,
		] );

		$this->dispatch_queue();

		$this->assertFalse( $_SERVER['__closure_job'] );
		$this->assertTrue( $_SERVER['__failed_run'] );

		$this->assertDatabaseDoesNotHave( 'mantle_queue', [ 'queue' => 'default', 'status' => Status::PENDING->value ] );
	}

	public function test_dispatch_job_delay() {
		$_SERVER['__example_job'] = false;

		$start = now()->addMonth();

		Example_Job::dispatch()->delay( $start );

		$this->assertDatabaseHas( 'mantle_queue', [
			'queue'  => 'default',
			'status' => Status::PENDING->value,
			'scheduled_date_gmt' => $start->toDateTimeString(),
		] );

		$this->dispatch_queue();

		$this->assertFalse( $_SERVER['__example_job'] );

		$this->assertDatabaseHas(
			'mantle_queue',
			[
				'queue' => 'default',
				'status' => Status::PENDING->value,
				'available_at_gmt' => $start->toDateTimeString(),
			],
		);
	}

	public function test_dispatch_to_named_queue_schedules_that_queue(): void {
		Example_Job::dispatch()->on_queue( 'emails' );

		$this->app->make( Database_Scheduler::class )->schedule_on_shutdown();

		$this->assertTrue( Database_Scheduler::get_scheduled_count( 'emails' ) > 0 );
		$this->assertSame( 0, Database_Scheduler::get_scheduled_count( 'default' ) );
	}

	public function test_dispatch_with_future_delay_schedules_run_for_when_it_is_available(): void {
		Example_Job::dispatch()->delay( 3600 );

		$this->app->make( Database_Scheduler::class )->schedule_on_shutdown();

		$this->assertCronCount( Database_Scheduler::EVENT, 1 );
		$this->assertSame( 0, Database_Scheduler::get_scheduled_count( 'default', time() + 60 ) );
	}

	public function test_lock_times_are_stored_in_utc_on_non_utc_sites(): void {
		update_option( 'timezone_string', 'America/New_York' );

		Example_Job::dispatch();

		$record = Database_Job_Record::query()->where( 'status', Status::PENDING->value )->first();

		$this->assertNotNull( $record );
		$this->assertEqualsWithDelta( time(), Carbon::parse( $record->created_date_gmt, 'UTC' )->getTimestamp(), 5 );

		$this->assertTrue( $record->claim( now()->addMinutes( 10 ) ) );
		$this->assertTrue( $record->is_locked() );
		$this->assertTrue( Database_Job_Record::find( $record->id )->is_locked() );
	}

	public function test_job_that_keeps_crashing_its_worker_is_failed(): void {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		$record = Database_Job_Record::query()->where( 'status', Status::PENDING->value )->first();

		// Simulate a worker that claimed the job and then crashed without recording a failure.
		$record->save( [
			'status'           => Status::RUNNING->value,
			'attempts'         => 1,
			'available_at_gmt' => now( 'UTC' )->subMinutes( 15 )->toDateTimeString(),
		] );

		$this->dispatch_queue();

		$this->assertFalse( $_SERVER['__example_job'] );
		$this->assertSame( Status::FAILED->value, Database_Job_Record::find( $record->id )->status );
	}

	public function test_job_reclaimed_by_another_worker_is_skipped(): void {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		$job = $this->app['queue']->get_provider()->pop( 'default', 1 )->first();

		$this->assertNotNull( $job );

		// Another worker reclaims the job after this worker's lock expired.
		Database_Job_Record::query()->first()->save( [
			'available_at_gmt' => now( 'UTC' )->addHour()->toDateTimeString(),
		] );

		$this->assertFalse( $job->reserve() );
	}

	public function test_job_with_a_long_class_name_can_be_dispatched(): void {
		$_SERVER['__example_job'] = false;

		Job_With_An_Exceptionally_Long_Class_Name_For_Testing_The_Type_Column::dispatch();

		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__example_job'] );
	}

	public function test_job_count_assertion_only_counts_that_job(): void {
		Example_Job::dispatch();
		Job_To_Fail::dispatch();

		$this->assertJobQueued( Example_Job::class, count: 1 );
	}

	public function test_saving_a_record_without_changes_does_not_throw(): void {
		Example_Job::dispatch();

		$record = Database_Job_Record::query()->first();

		$this->assertTrue( $record->save() );
		$this->assertTrue( $record->save( [ 'status' => $record->status ] ) );
	}

	public function test_where_with_an_array_value_queries_with_in(): void {
		Example_Job::dispatch();

		$this->assertSame( 1, Database_Job_Record::query()->where( 'status', [ Status::PENDING->value, Status::FAILED->value ] )->count() );
		$this->assertSame( 0, Database_Job_Record::query()->where( 'status', [ Status::FAILED->value ] )->count() );
		$this->assertSame( 0, Database_Job_Record::query()->whereIn( 'status', [] )->count() );
	}

	public function test_dispatch_inside_switch_to_blog_uses_that_sites_table(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		global $wpdb;

		$blog_id = static::factory()->blog->create();

		switch_to_blog( $blog_id );

		Example_Job::dispatch();

		$this->assertSame( $wpdb->prefix . 'mantle_queue', $wpdb->mantle_queue );
		$this->assertSame( 1, Database_Job_Record::query()->count() );

		restore_current_blog();

		$this->assertSame( 0, Database_Job_Record::query()->count() );
	}

	public function test_cleanup_deletes_legacy_post_jobs(): void {
		$post_id = static::factory()->post->create( [
			'post_type'   => 'mantle_queue',
			'post_status' => 'queue_pending',
		] );

		$this->command( 'mantle queue:cleanup', [ '--legacy' => true ] )
			->assertOk()
			->assertOutputContains( 'Deleted 1 job.' );

		$this->assertNull( get_post( $post_id ) );
	}
}

class Example_Job implements Job, Can_Queue {
	use Queueable, Dispatchable;

	public function handle(): void {
		$_SERVER['__example_job'] = true;
	}
}

class Job_To_Fail implements Job, Can_Queue {
	use Queueable, Dispatchable;

	public function handle(): void {
		throw new RuntimeException( 'Something went wrong' );
	}

	public function failed(): void {
		$_SERVER['__failed_run']++;
	}
}

class Job_To_Fail_Retry extends Job_To_Fail {
	public bool $retry = true;

	public int $retry_backoff = 30;

	public int $tries = 3;
}

class Job_With_An_Exceptionally_Long_Class_Name_For_Testing_The_Type_Column extends Example_Job {}
