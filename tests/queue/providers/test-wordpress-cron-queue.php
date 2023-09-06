<?php
namespace Mantle\Tests\Queue\Providers;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Job;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Events\Job_Failed;
use Mantle\Queue\Events\Job_Queued;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Providers\WordPress\Meta_Key;
use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Queueable;
use Mantle\Queue\Providers\WordPress\Provider;
use Mantle\Queue\Providers\WordPress\Queue_Job;
use Mantle\Queue\Providers\WordPress\Queue_Worker_Job;
use Mantle\Queue\Providers\WordPress\Scheduler;
use Mantle\Queue\Queue_Job_Locked_Exception;
use Mantle\Testing\Concerns\Refresh_Database;
use PHPUnit\Framework\Assert;
use RuntimeException;

use function Mantle\Queue\dispatch;

/**
 * WordPress Cron Queue Provider Test
 *
 * @group queue
 * @group wordpress-queue
 */
class Test_WordPress_Cron_Queue extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		// TODO: Remove once typehint is fixed.
		remove_all_filters( Run_Complete::class );
		remove_all_filters( Job_Queued::class );

		parent::setUp();

		Provider::register_data_types();
	}

	public function test_job_dispatch() {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		$this->assertInCronQueue( Example_Job::class );
		$this->assertFalse( $_SERVER['__example_job'] );

		// Assert that the underlying queue post exists.
		$this->assertPostExists( [
			'post_type'    => Provider::OBJECT_NAME,
			'post_status'  => Post_Status::PENDING->value,
		] );

		// Force the cron to be dispatched which will execute
		// the queued job.
		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__example_job'] );

		// Ensure that the queued job post was deleted.
		$this->assertPostDoesNotExists( [
			'post_type'   => Provider::OBJECT_NAME,
			'post_status' => 'any',
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
			'post_status' => 'any',
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
			'post_status' => Post_Status::FAILED->value,
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
				Post_Status::PENDING->value,
				Post_Status::FAILED->value,
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
			'post_status'  => Post_Status::PENDING->value,
			'post_type'    => Provider::OBJECT_NAME,
			'meta_key'     => '_mantle_queue',
			'meta_value'   => 'SerializableClosure',
			'meta_compare' => 'LIKE',
		] );

		$this->dispatch_queue();

		$this->assertFalse( $_SERVER['__closure_job'] );
		$this->assertTrue( $_SERVER['__failed_run'] );

		$this->assertPostExists( [
			'post_status'  => Post_Status::FAILED->value,
			'post_type'    => Provider::OBJECT_NAME,
			'meta_key'     => '_mantle_queue',
			'meta_value'   => 'SerializableClosure',
			'meta_compare' => 'LIKE',
		] );
	}

	// public function test_exception_thrown_locked_job() {
	// 	$this->expectException( Queue_Job_Locked_Exception::class );
	// 	$this->expectExceptionMessage( 'Queue job is locked: ' . Example_Job::class );

	// 	$_SERVER['__failed_run']  = false;

	// 	$this->app['events']->listen(
	// 		Job_Failed::class,
	// 		fn () => $_SERVER['__failed_run'] = true,
	// 	);

	// 	$model = Queue_Job::first_or_create( [
	// 		'post_status' => Post_Status::PENDING->value,
	// 	] );

	// 	$model->set_terms(
	// 		[
	// 			Provider::OBJECT_NAME => Provider::get_queue_term_id( 'default' ),
	// 		]
	// 	);

	// 	$model->set_meta( Meta_Key::LOCK_UNTIL->value, time() + 600 );
	// 	$model->set_meta( Meta_Key::JOB->value, new Example_Job( false ) );

	// 	$job = new Queue_Worker_Job( $model );

	// 	$job->fire();
	// }

	// public function test_unlocked_after_exception_thrown() {
	// 	$_SERVER['__failed_run']  = false;

	// 	$this->app['events']->listen(
	// 		Job_Failed::class,
	// 		fn () => $_SERVER['__failed_run'] = true,
	// 	);

	// 	$model = Queue_Job::first_or_create( [
	// 		'post_status' => Post_Status::PENDING->value,
	// 	] );

	// 	$model->set_terms(
	// 		[
	// 			Provider::OBJECT_NAME => Provider::get_queue_term_id( 'default' ),
	// 		]
	// 	);

	// 	$model->set_meta( Meta_Key::LOCK_UNTIL->value, time() + 600 );
	// 	$model->set_meta( Meta_Key::JOB->value, new Example_Job( false ) );

	// 	$job = new Queue_Worker_Job( $model );

	// 	try {
	// 		$job->fire();
	// 	} catch ( \Throwable $e ) {
	// 		$job->failed( $e );
	// 	}

	// 	$this->assertEmpty( $model->get_meta( Meta_Key::LOCK_UNTIL->value ) );
	// 	$this->assertNotEmpty( $model->get_meta( Meta_Key::FAILURE->value ) );
	// }

	//////////////////////

	// public function test_schedule_next_run_after_complete() {
	// 	// Limit the queue batch size.
	// 	$this->app['config']->set( 'queue.batch_size', 5 );

	// 	for ( $i = 0; $i < 8; $i++ ) {
	// 		Example_Job::dispatch();
	// 	}

	// 	// $this->assertInCronQueue( Scheduler::EVENT, [ 'default' ] );

	// 	$this->dispatch_queue();

	// 	// $this->assertInCronQueue( Scheduler::EVENT, [ 'default' ] );

	// 	$this->dispatch_queue();

	// 	// $this->assertNotInCronQueue( Scheduler::EVENT, [ 'default' ] );
	// }
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
}
