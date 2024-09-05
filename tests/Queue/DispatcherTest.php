<?php
namespace Mantle\Tests\Queue;

use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Job;
use Mantle\Contracts\Queue\Provider;
use Mantle\Facade\Facade;
use Mantle\Queue\Queue_Service_Provider;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Dispatcher;
use Mantle\Queue\Queue_Manager;
use Mockery as m;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group queue
 */
#[Group( 'queue' )]
class DispatcherTest extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	/**
	 * Provider instance.
	 *
	 * @var Provider
	 */
	protected $provider;

	/**
	 * Queue Manager instance.
	 *
	 * @var Queue_Manager
	 */
	protected $queue;

	protected $app;

	protected function setUp(): void {
		parent::setUp();

		$this->provider = m::mock( Provider::class );

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

		$this->queue = $this->app['queue'];
		$this->queue->add_provider( 'test', $this->provider );

		Facade::clear_resolved_instances();
		Facade::set_facade_application( $this->app );
	}

	public function test_dispatch_to_provider() {
		$job = m::mock( Job::class, Can_Queue::class );
		$this->provider
			->shouldReceive( 'push' )
			->withArgs( [ $job ] )
			->once()
			->andReturn( true );

		$dispatcher = new Dispatcher( $this->app );
		$dispatcher->dispatch( $job );
	}

	public function test_dispatch_non_queueable() {
		$job = m::mock( Job::class );
		$job->shouldReceive( 'handle' )->once();

		$dispatcher = new Dispatcher( $this->app );
		$dispatcher->dispatch( $job );
	}

	public function test_pending_dispatch() {
		$job = m::mock( Job::class, Dispatchable::class, Can_Queue::class );
		$this->provider
			->shouldReceive( 'push' )
			->with( m::type( get_class( $job ) ) )
			->once();

		get_class( $job )::dispatch( [] );
	}

	public function test_pending_dispatch_if() {
		$job = m::mock( Job::class, Dispatchable::class, Can_Queue::class );
		$this->provider
			->shouldReceive( 'push' )
			->with( m::type( get_class( $job ) ) )
			->times( 2 );

		get_class( $job )::dispatch_if( true, [] );
		get_class( $job )::dispatch_if( true, [] );
		get_class( $job )::dispatch_if( false, [] );
	}

	public function test_dispatch_after_response() {
		$job = m::mock( Job::class, Dispatchable::class, Can_Queue::class, [ 'handle' => null ] );
		$job->shouldReceive( 'handle' )->once();

		$dispatcher = new Dispatcher( $this->app );
		$dispatcher->dispatch_after_response( $job );

		// Terminating the application should dispatch the job.
		$this->app->terminate();
	}
}
