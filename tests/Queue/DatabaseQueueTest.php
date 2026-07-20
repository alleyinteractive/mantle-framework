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
