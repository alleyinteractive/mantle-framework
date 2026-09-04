<?php
namespace Mantle\Tests\Queue;

use Mantle\Queue\Concerns\Database_Queue_Schema;
use Mantle\Queue\Database_Scheduler;
use Mantle\Queue\Jobs\Database_Job_Record;
use Mantle\Queue\Jobs\Status;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Queue\dispatch;

require_once __DIR__ . '/DatabaseQueueTest.php';

/**
 * WordPress Database Queue Provider Test
 *
 * The use of dispatch_queue() in this test is discouraged, as it is doesn't
 * dispatch the cron and only dispatches the queued jobs. This test should aim
 * to focus on the interaction with the WordPress Cron API and the database
 * queue.
 *
 * @group queue
 * @group wordpress-queue
 */
#[Group( 'queue' )]
#[Group( 'wordpress-queue' )]
class DatabaseSchedulerTest extends FrameworkTestCase {
	use Database_Queue_Schema;
	use Refresh_Database;

	protected Database_Scheduler $scheduler;

	protected function setUp(): void {
		wp_unschedule_hook( Database_Scheduler::EVENT );

		parent::setUp();

		$this->scheduler = $this->app->make( Database_Scheduler::class );

		$_SERVER['__example_job'] = false;
	}

	public function test_dispatch_schedules_job(): void {
		$this->assertNotInCronQueue( Database_Scheduler::EVENT );
		$this->assertJobNotQueued( Example_Job::class );

		Example_Job::dispatch();

		// Ensure the job is now queued.
		$this->assertJobQueued( Example_Job::class, [], 'default' );

		// Expect the cron job to be scheduled yet (that happens on shutdown).
		$this->assertNotInCronQueue( Database_Scheduler::EVENT );

		$this->scheduler->schedule_on_shutdown();

		// Expect the cron job to be scheduled now.
		$this->assertInCronQueue( Database_Scheduler::EVENT );

		// Dispatch the queue via the cron.
		$this->dispatch_cron( Database_Scheduler::EVENT, fail_empty: true );

		// Ensure the job ran.
		$this->assertTrue( $_SERVER['__example_job'] );

		// Ensure the job is no longer queued and the cron job is not scheduled.
		$this->assertJobNotQueued( Example_Job::class );
		$this->assertNotInCronQueue( Database_Scheduler::EVENT );
	}

	public function test_schedule_batches(): void {
		$this->app['config']->set( 'queue.batch_size', 5 );
		$this->app['config']->set( 'queue.max_concurrent_batches', 5 );
		$this->app['config']->set( 'queue.wordpress.delay', 0 );

		// Dispatch 8 jobs, which should be more than the batch size.
		// The first 5 will be processed, and the next 3 will be queued
		// for the next run.
		for ( $i = 0; $i < 8; $i++ ) {
			Example_Job::dispatch();
		}

		$this->assertJobQueued( Example_Job::class );
		$this->assertJobQueued( Example_Job::class, count: 8 );

		// Ensure the cron job is not scheduled yet.
		$this->assertCronNotScheduled( Database_Scheduler::EVENT );

		$this->scheduler->schedule_on_shutdown();

		// Ensure the cron job is scheduled to run twice, since we have 8 jobs
		// and the batch size is 5. The first run will process 5 jobs
		// and the second run will process the remaining 3 jobs.
		$this->assertCronScheduled( Database_Scheduler::EVENT );
		$this->assertCronCount( Database_Scheduler::EVENT, 2 );

		// Dispatch the cron job to process the first batch of 5.
		$this->assertEquals( 1, $this->dispatch_cron( Database_Scheduler::EVENT ) );

		// Ensure the first batch ran and that there are still jobs queued.
		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertJobQueued( Example_Job::class, count: 3 );

		// Ensure the next cron job is still scheduled.
		$this->assertCronScheduled( Database_Scheduler::EVENT );
		$this->assertCronCount( Database_Scheduler::EVENT, 1 );

		$_SERVER['__example_job'] = false;

		// Dispatch the next 1 cron job to process the remaining 3 jobs.
		$this->dispatch_cron( Database_Scheduler::EVENT, fail_empty: true, future: true );

		// Ensure the second batch ran and that there are no jobs left queued.
		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertJobNotQueued( Example_Job::class );

		// Ensure the cron job is no longer scheduled.
		$this->assertCronNotScheduled( Database_Scheduler::EVENT );
	}

	public function test_schedule_multiple_queue_workers() {
		$this->app['config']->set( 'queue.max_concurrent_batches', 10 );
		$this->app['config']->set( 'queue.batch_size', 10 );

		for ( $i = 0; $i < 100; $i++ ) {
			Example_Job::dispatch();
		}

		$this->assertFalse( $_SERVER['__example_job'] );

		// Ensure that the 100 jobs are queued.
		$this->assertJobQueued( Example_Job::class );
		$this->assertJobQueued( Example_Job::class, count: 100 );

		// The cron event shouldn't be scheduled yet, as we haven't
		// fired the "shutdown" event to schedule the queue jobs.
		$this->assertCronNotScheduled( Database_Scheduler::EVENT );

		// Fire the "shutdown" event to schedule the queue jobs.
		$this->scheduler->schedule_on_shutdown();

		// With max_concurrent_batches set to 10 and 100 jobs dispatched, we should
		// have 10 queue jobs scheduled to run.
		$this->assertCronScheduled( Database_Scheduler::EVENT );
		$this->assertCronCount( Database_Scheduler::EVENT, 10 );
		$this->assertEquals( 10, Database_Scheduler::get_scheduled_count() );

		$this->dispatch_cron( Database_Scheduler::EVENT, fail_empty: true );

		// Expect that the first batch of 10 jobs ran.
		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertJobQueued( Example_Job::class );
		$this->assertJobQueued( Example_Job::class, count: 90 );

		// Ensure that there are still cron jobs scheduled to process the remaining
		// 90 jobs.
		$this->assertCronScheduled( Database_Scheduler::EVENT );
		$this->assertCronCount( Database_Scheduler::EVENT, 9 );
	}

	public function test_cleanup_completed_jobs() {
		$this->app['config']->set( 'queue.delete_after', 60 * 60 * 24 );

		$record = Database_Job_Record::create( [
			'status' => Status::COMPLETED->value,
			'created_date_gmt' => now()->subMonth()->format( 'Y-m-d H:i:s' ),
			'scheduled_date_gmt' => now()->subMonth()->format( 'Y-m-d H:i:s' ),
		] );

		// Create a valid queue job that shouldn't be deleted.
		Example_Job::dispatch();

		// Perform the scheduled cleanup manually.
		$this->command( 'mantle queue:cleanup' )
			->assertOk()
			->assertOutputContains( 'Deleted 1 job.' );

		// Ensure that the expired queue job was deleted.
		$this->assertEmpty( Database_Job_Record::find( $record->id ) );

		// Ensure that the pending job is still there.
		$this->assertInCronQueue( Example_Job::class );
	}

	public function test_dispatch_with_job_delay(): void {
		$this->markTestIncomplete(
			'The system will schedule the cron job to run immediately, but the job itself will not run until the delay has passed. ' .
			' This could be improved in the future to allow for a delay to be set on the cron job itself, ' .
			' so that the job is not scheduled until the delay has passed.'
		);
	}

	public function test_dispatch_after_response() {
		$_SERVER['__example_job'] = false;

		dispatch( fn () => $_SERVER['__example_job'] = true )->after_response();

		$this->assertFalse( $_SERVER['__example_job'] );

		$this->app->terminate();

		$this->assertTrue( $_SERVER['__example_job'] );
	}
}
