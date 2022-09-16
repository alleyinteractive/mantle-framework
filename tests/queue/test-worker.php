<?php
namespace Mantle\Tests\Queue;

use Mantle\Framework\Application;
use Mantle\Config\Repository;
use Mantle\Contracts\Queue\Provider;
use Mantle\Queue\Queue_Service_Provider;
use Mantle\Queue\Events;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Events\Run_Start;
use Mantle\Queue\Job;
use Mantle\Support\Collection;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use function Mantle\Support\Helpers\collect;

/**
 * @group queue
 */
class Test_Worker extends MockeryTestCase {

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	protected function setUp(): void {
		parent::setUp();

		$config = new Repository(
			[
				'queue' => [
					'default' => 'test',
				],
			]
		);

		$this->app = new Application();
		$this->app->instance( 'config', $config );

		// Load the queue service provider manually.
		$queue_provider = new Queue_Service_Provider( $this->app );
		$queue_provider->register();
		$queue_provider->boot();

		// Register the testable provider.
		$this->app['queue']->add_provider( 'test', Testable_Provider::class );

		// Remove the conflicting events from the previous runs.
		remove_all_filters( Events\Job_Processed::class );
		remove_all_filters( Events\Job_Processing::class );
		remove_all_filters( Events\Run_Complete::class );
		remove_all_filters( Events\Run_Start::class );
	}

	public function test_event_fire() {
		$this->app['queue']->get_provider( 'test' )->push( $this->get_mock_job( 1 ) );
		$this->app['queue']->get_provider( 'test' )->push( $this->get_mock_job( 2 ) );
		$this->app['queue']->get_provider( 'test' )->push( $this->get_mock_job( 3 ) );

		$this->app['queue.worker']->run( 3 );
	}

	public function test_event_listener() {
		$events_fired = [];
		$callback     = function( $event ) use ( &$events_fired ) {
			$events_fired[] = get_class( $event );
		};

		$this->app['events']->listen( Events\Run_Start::class, $callback );
		$this->app['events']->listen( Events\Job_Processing::class, $callback );
		$this->app['events']->listen( Events\Job_Processed::class, $callback );
		$this->app['events']->listen( Events\Run_Complete::class, $callback );

		$this->app['queue']->get_provider( 'test' )->push( $this->get_mock_job( 1 ) );
		$this->app['queue']->get_provider( 'test' )->push( $this->get_mock_job( 2 ) );
		$this->app['queue.worker']->run( 2 );

		$this->assertCount( 6, $events_fired );
		$this->assertEquals(
			[
				Events\Run_Start::class,
				Events\Job_Processing::class,
				Events\Job_Processed::class,
				Events\Job_Processing::class,
				Events\Job_Processed::class,
				Events\Run_Complete::class,
			],
			$events_fired
		);
	}

	public function test_closure_job() {
		$job = fn () => $_SERVER['__closure_run'] = true;

		$this->app['queue']->get_provider( 'test' )->push( $job );

		$this->app['queue.worker']->run( 1 );

		$this->assertTrue( $_SERVER['__closure_run'] );

		$this->app['queue.worker']->run( 1 );
	}

	protected function get_mock_job( $id, $should_run = true ) {
		$mock_job = m::mock( Job::class );

		if ( $should_run ) {
			$mock_job->shouldReceive( 'fire' )->once()->andReturn( true );
			$mock_job->shouldReceive( 'delete' )->once();
			$mock_job->shouldReceive( 'has_failed' )->once()->andReturn( false );
			$mock_job->shouldNotReceive( 'failed' );
		}

		$mock_job->shouldReceive( 'get_id' )->andReturn( $id );

		return $mock_job;
	}
}

class Testable_Provider implements Provider {
	protected $jobs = [];

	/**
	 * Push a job to the queue.
	 *
	 * @param mixed $job Job instance.
	 * @return bool
	 */
	public function push( $job ) {
		$this->jobs[] = $job;
		return true;
	}

	/**
	 * Get the next set of jobs in the queue.
	 *
	 * @param string $queue Queue name, optional.
	 * @param int    $count Number of items to return.
	 * @return Collection
	 */
	public function pop( string $queue = null, int $count = 1 ): Collection {
		return collect(
			array_slice( $this->jobs, 0, $count )
		);
	}

	/**
	 * Check if a job is in the queue.
	 *
	 * @param mixed  $job Job instance.
	 * @param string $queue Queue to compare against.
	 * @return bool
	 */
	public function in_queue( $job, string $queue = null ): bool {
		return false;
	}
}
