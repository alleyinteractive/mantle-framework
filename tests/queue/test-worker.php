<?php
namespace Mantle\Tests\Queue;

use Mantle\Framework\Application;
use Mantle\Framework\Config\Repository;
use Mantle\Framework\Contracts\Queue\Provider;
use Mantle\Framework\Providers\Queue_Service_Provider;
use Mantle\Framework\Queue\Events\Job_Processed;
use Mantle\Framework\Queue\Events\Job_Processing;
use Mantle\Framework\Queue\Events\Run_Complete;
use Mantle\Framework\Queue\Events\Run_Start;
use Mantle\Framework\Queue\Job;
use Mantle\Framework\Support\Collection;
use Mockery as m;

use function Mantle\Framework\Helpers\collect;

class Test_Worker extends \Mockery\Adapter\Phpunit\MockeryTestCase {
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

		$this->app['events']->listen( Run_Start::class, $callback );
		$this->app['events']->listen( Job_Processing::class, $callback );
		$this->app['events']->listen( Job_Processed::class, $callback );
		$this->app['events']->listen( Run_Complete::class, $callback );

		$this->app['queue']->get_provider( 'test' )->push( $this->get_mock_job( 1 ) );
		$this->app['queue']->get_provider( 'test' )->push( $this->get_mock_job( 2 ) );
		$this->app['queue.worker']->run( 2 );

		$this->assertCount( 6, $events_fired );
		$this->assertEquals(
			[
				Run_Start::class,
				Job_Processing::class,
				Job_Processed::class,
				Job_Processing::class,
				Job_Processed::class,
				Run_Complete::class,
			],
			$events_fired
		);
	}

	protected function get_mock_job( $id, $should_run = true ) {
		$mock_job = m::mock( Job::class );

		if ( $should_run ) {
			$mock_job->shouldReceive( 'fire' )->once();
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
}
