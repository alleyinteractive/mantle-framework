<?php
namespace Mantle\Tests\Queue\Providers;

use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Job;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Events\Job_Failed;
use Mantle\Queue\Providers\WordPress\Meta_Key;
use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Provider;
use Mantle\Queue\Providers\WordPress\Scheduler;
use Mantle\Queue\Queueable;
use Mantle\Scheduling\Schedule;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Assert;
use RuntimeException;

use function Mantle\Queue\dispatch;
use function Mantle\Support\Helpers\collect;

/**
 * WordPress Cron Queue Provider Test
 *
 * @group queue
 * @group wordpress-queue
 */
class Test_WordPress_Cron_Queue extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'PHP 8.1 or greater is required for the queue' );
		}

		parent::setUp();

		Provider::register_data_types();
	}

	public function test_cron_action() {
		$this->assertTrue( has_action( Scheduler::EVENT ) );
	}

	public function test_job_dispatch() {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		$this->assertInCronQueue( Example_Job::class );
		$this->assertFalse( $_SERVER['__example_job'] );

		// Assert that the underlying queue post exists.
		$this->assertPostExists( [
			'post_type'    => Provider::OBJECT_NAME,
			'post_status'  => Post_Status::PENDING,
		] );

		// Force the cron to be dispatched which will execute the queued job.
		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__example_job'] );

		// Ensure that the queued job post was deleted.
		$this->assertPostExists( [
			'post_type'   => Provider::OBJECT_NAME,
			'post_status' => Post_Status::COMPLETED,
		] );
	}

	public function test_job_dispatch_now() {
		$this->assertNotInCronQueue( Example_Job::class );

		$_SERVER['__example_job'] = false;

		Example_Job::dispatch_now( false );

		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertNotInCronQueue( Example_Job::class );

		$this->assertPostDoesNotExists( [
			'post_type'   => Provider::OBJECT_NAME,
			'post_status' => Post_Status::class,
		] );
	}

	public function test_job_failure() {
		$_SERVER['__failed_run'] = false;

		$this->app['events']->listen( Job_Failed::class, fn () => $_SERVER['__failed_run'] = true );

		$this->assertNotInCronQueue( Job_To_Fail::class );

		Job_To_Fail::dispatch();

		$this->assertInCronQueue( Job_To_Fail::class );

		$this->dispatch_queue();

		$this->assertNotInCronQueue( Job_To_Fail::class );
		$this->assertTrue( $_SERVER['__failed_run'] );

		// Ensure that the post does not exist.
		$this->assertPostExists( [
			'post_type'   => Provider::OBJECT_NAME,
			'post_status' => Post_Status::FAILED,
		] );
	}

	public function test_job_dispatch_anonymous() {
		$_SERVER['__closure_job'] = false;

		dispatch( function() {
			$_SERVER['__closure_job'] = true;
		} );

		// Assert the serialize queue post exists.
		$this->assertPostExists( [
			'post_type'    => Provider::OBJECT_NAME,
			'post_status'  => Post_Status::PENDING->value,
			'meta_key'     => '_mantle_queue',
			'meta_value'   => 'SerializableClosure',
			'meta_compare' => 'LIKE',
		] );

		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__closure_job'] );

		$this->assertPostDoesNotExists( [
			'post_type'    => Provider::OBJECT_NAME,
			'meta_key'     => '_mantle_queue',
			'meta_value'   => 'SerializableClosure',
			'meta_compare' => 'LIKE',
			'post_status'  => [
				Post_Status::PENDING,
				Post_Status::FAILED,
			],
		] );
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
		$this->assertPostExists( [
			'post_status'  => Post_Status::PENDING,
			'post_type'    => Provider::OBJECT_NAME,
			'meta_key'     => '_mantle_queue',
			'meta_value'   => 'SerializableClosure',
			'meta_compare' => 'LIKE',
		] );

		$this->dispatch_queue();

		$this->assertFalse( $_SERVER['__closure_job'] );
		$this->assertTrue( $_SERVER['__failed_run'] );

		$this->assertPostExists( [
			'post_status'  => Post_Status::FAILED,
			'post_type'    => Provider::OBJECT_NAME,
			'meta_key'     => '_mantle_queue',
			'meta_value'   => 'SerializableClosure',
			'meta_compare' => 'LIKE',
		] );
	}

	public function test_dispatch_job_delay() {
		$_SERVER['__example_job'] = false;

		$start = now()->addMonth();

		Example_Job::dispatch()->delay( $start );

		$this->assertPostExists( [
			'post_date'    => $start->toDateTimeString(),
			'post_status'  => Post_Status::PENDING,
			'post_type'    => Provider::OBJECT_NAME,
			'meta_key'     => '_mantle_queue',
		] );

		$this->dispatch_queue();

		$this->assertFalse( $_SERVER['__example_job'] );

		$this->assertPostExists( [
			'post_date'    => $start->toDateTimeString(),
			'post_status'  => Post_Status::PENDING,
			'post_type'    => Provider::OBJECT_NAME,
			'meta_key'     => '_mantle_queue',
		] );
	}

	public function test_schedule_multiple_queue_workers() {
		$this->app['config']->set( 'queue.max_concurrent_batches', 10 );
		$this->app['config']->set( 'queue.batch_size', 10 );

		for ( $i = 0; $i < 100; $i++ ) {
			Example_Job::dispatch();
		}

		// Fire the "shutdown" event to schedule the queue jobs.
		Scheduler::schedule_on_shutdown();

		// With max_concurrent_batches set to 10 and 100 jobs dispatched, we should
		// have 10 queue jobs scheduled to run.
		$this->assertEquals( 10, Scheduler::get_scheduled_count() );

		$this->dispatch_queue( 100 );

		Scheduler::schedule_on_shutdown();

		// Ensure the scheduled jobs are cleaned up.
		$this->assertEquals( 0, Scheduler::get_scheduled_count() );
	}

	public function test_failed_job() {
		$_SERVER['__failed_run'] = 0;

		Job_To_Fail::dispatch();

		$this->assertInCronQueue( Job_To_Fail::class );

		$this->dispatch_queue();

		$this->assertNotInCronQueue( Job_To_Fail::class );
		$this->assertEquals( 1, $_SERVER['__failed_run'] );

		$this->assertPostExists( [
			'post_type'    => Provider::OBJECT_NAME,
			'post_status'  => Post_Status::FAILED,
		] );

		$this->dispatch_queue();
	}

	public function test_retry_failed_job() {
		$_SERVER['__failed_run'] = 0;

		Job_To_Fail_Retry::dispatch();

		$this->assertInCronQueue( Job_To_Fail_Retry::class );

		$this->dispatch_queue();

		// First failure.
		$this->assertEquals( 1, $_SERVER['__failed_run'] );
		$this->assertInCronQueue( Job_To_Fail_Retry::class );

		$this->dispatch_queue();

		// Ensure it didn't run (it should be delayed 30 seconds).
		$this->assertEquals( 1, $_SERVER['__failed_run'] );
		$this->assertInCronQueue( Job_To_Fail_Retry::class );
	}

	public function test_schedule_next_run_after_complete() {
		// Limit the queue batch size.
		$this->app['config']->set( 'queue.batch_size', 5 );

		for ( $i = 0; $i < 8; $i++ ) {
			Example_Job::dispatch();
		}

		$this->assertJobQueued( Example_Job::class, [], 'default' );

		// Ensure the next job is scheduled.
		Scheduler::schedule_on_shutdown();
		$this->assertInCronQueue( Scheduler::EVENT, null );

		$this->dispatch_queue( 2 );

		$this->assertJobQueued( Example_Job::class, [], 'default' );

		// Ensure the next job is scheduled.
		Scheduler::schedule_on_shutdown();
		$this->assertInCronQueue( Scheduler::EVENT, null );

		$this->dispatch_queue( 6 );

		$this->assertJobNotQueued( Example_Job::class, [], 'default' );

		// Ensure the next job is not scheduled.
		Scheduler::schedule_on_shutdown();
		$this->assertNotInCronQueue( Scheduler::EVENT, null );
	}
}

class Example_Job implements Job, Can_Queue {
	use Queueable, Dispatchable;

	public function __construct( public bool $assert = true ) {}

	public function handle() {
		$_SERVER['__example_job'] = true;

		if ( ! $this->assert ) {
			return;
		}

		// Fetch the job post.
		$jobs = get_posts(
			[
				'fields'         => 'ids',
				'post_status'    => Post_Status::RUNNING->value,
				'post_type'      => Provider::OBJECT_NAME,
				'posts_per_page' => 1,
			]
		);

		Assert::assertCount( 1, $jobs );
		Assert::assertGreaterThan( \time(), get_post_meta( $jobs[0], Meta_Key::LOCK_UNTIL->value, true ) );
	}
}

class Job_To_Fail implements Job, Can_Queue {
	use Queueable, Dispatchable;

	public function handle() {
		throw new RuntimeException( 'Something went wrong' );
	}

	public function failed(): void {
		$_SERVER['__failed_run']++;
	}
}

class Job_To_Fail_Retry extends Job_To_Fail {
	public bool $retry = true;

	public int $retry_backoff = 30;
}
