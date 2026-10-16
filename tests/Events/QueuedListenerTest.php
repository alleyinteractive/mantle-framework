<?php
namespace Mantle\Tests\Events;

use Mantle\Container\Container;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Dispatcher as Queue_Dispatcher;
use Mantle\Events\Call_Queued_Listener;
use Mantle\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

#[Group( 'events' )]
class QueuedListenerTest extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	protected Container $container;

	protected Recording_Queue_Dispatcher $queue;

	protected function setUp(): void {
		parent::setUp();

		$_SERVER['__queued_listener'] = [];

		$this->container = new Container();
		$this->queue     = new Recording_Queue_Dispatcher();

		$this->container->instance( Queue_Dispatcher::class, $this->queue );
	}

	protected function tearDown(): void {
		unset( $_SERVER['__queued_listener'] );

		parent::tearDown();
	}

	public function test_queued_listener_is_pushed_instead_of_run(): void {
		$events = new Dispatcher( $this->container );
		$events->listen( __FUNCTION__, Queued_Listener::class );

		$events->dispatch( __FUNCTION__, 'foo', 'bar' );

		$this->assertSame( [], $_SERVER['__queued_listener'] );
		$this->assertCount( 1, $this->queue->jobs );

		$job = $this->queue->jobs[0];

		$this->assertInstanceOf( Call_Queued_Listener::class, $job );
		$this->assertInstanceOf( Can_Queue::class, $job );
		$this->assertSame( Queued_Listener::class, $job->class );
		$this->assertSame( 'handle', $job->method );
		$this->assertSame( [ 'foo', 'bar' ], $job->data );
	}

	public function test_running_the_job_invokes_the_listener_with_the_payload(): void {
		$events = new Dispatcher( $this->container );
		$events->listen( __FUNCTION__, Queued_Listener::class );
		$events->listen( __FUNCTION__, Queued_Listener::class . '@on_event' );

		$events->dispatch( __FUNCTION__, 'foo', 'bar' );

		$this->assertCount( 2, $this->queue->jobs );

		foreach ( $this->queue->jobs as $job ) {
			unserialize( serialize( $job ) )->handle(); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		}

		$this->assertSame(
			[
				[ 'handle', 'foo', 'bar' ],
				[ 'on_event', 'foo', 'bar' ],
			],
			$_SERVER['__queued_listener'],
		);
	}

	public function test_queued_listener_passes_the_filtered_value_through(): void {
		$events = new Dispatcher( $this->container );
		$events->listen( __FUNCTION__, Queued_Listener::class );

		$this->assertSame( 'foo', $events->dispatch( __FUNCTION__, 'foo', 'bar' ) );

		$events->listen( __FUNCTION__, fn ( $value ) => "{$value}-filtered", 20 );

		$this->assertSame( 'foo-filtered', $events->dispatch( __FUNCTION__, 'foo', 'bar' ) );
	}

	public function test_object_event_is_queued_and_returned(): void {
		$events = new Dispatcher( $this->container );
		$events->listen( Queued_Event::class, Queued_Listener::class );

		$event = new Queued_Event( 'payload' );

		$this->assertSame( $event, $events->dispatch( $event ) );
		$this->assertCount( 1, $this->queue->jobs );
		$this->assertSame( [ $event ], $this->queue->jobs[0]->data );
	}

	public function test_non_queued_listener_runs_inline(): void {
		$events = new Dispatcher( $this->container );
		$events->listen( __FUNCTION__, Inline_Listener::class );

		$events->dispatch( __FUNCTION__, 'foo' );

		$this->assertSame( [ [ 'inline', 'foo' ] ], $_SERVER['__queued_listener'] );
		$this->assertSame( [], $this->queue->jobs );
	}

	public function test_should_queue_false_runs_nothing(): void {
		$events = new Dispatcher( $this->container );
		$events->listen( __FUNCTION__, Conditional_Listener::class );

		$this->assertSame( 'skip', $events->dispatch( __FUNCTION__, 'skip' ) );

		$this->assertSame( [], $this->queue->jobs );
		$this->assertSame( [], $_SERVER['__queued_listener'] );

		$events->dispatch( __FUNCTION__, 'go' );

		$this->assertCount( 1, $this->queue->jobs );
		$this->assertSame( [], $_SERVER['__queued_listener'] );
	}

	public function test_listener_queue_and_delay_are_applied_to_the_job(): void {
		$events = new Dispatcher( $this->container );
		$events->listen( __FUNCTION__, Delayed_Listener::class );
		$events->listen( __FUNCTION__, Queued_Listener::class );

		$events->dispatch( __FUNCTION__, 'foo' );

		[ $delayed, $default ] = $this->queue->jobs;

		$this->assertSame( 'listeners', $delayed->queue );
		$this->assertSame( 60, $delayed->delay );
		$this->assertSame( 3, $delayed->tries );
		$this->assertSame( 30, $delayed->retry_backoff );
		$this->assertSame( 120, $delayed->timeout );
		$this->assertFalse( isset( $default->queue ) );
		$this->assertFalse( isset( $default->delay ) );
		$this->assertFalse( isset( $default->tries ) );
	}

	public function test_queued_listener_without_a_queue_throws(): void {
		$events = new Dispatcher( new Container() );
		$events->listen( __FUNCTION__, Queued_Listener::class );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( Queued_Listener::class );

		$events->dispatch( __FUNCTION__, 'foo' );
	}
}

class Recording_Queue_Dispatcher implements Queue_Dispatcher {
	public array $jobs = [];

	public function dispatch( $job ) {
		$this->jobs[] = $job;
	}

	public function dispatch_now( $job ) {
		$job->handle();
	}
}

class Queued_Event {
	public function __construct( public string $value ) {}
}

class Queued_Listener implements Can_Queue {
	public function handle( mixed ...$args ): void {
		$_SERVER['__queued_listener'][] = [ 'handle', ...$args ];
	}

	public function on_event( mixed ...$args ): void {
		$_SERVER['__queued_listener'][] = [ 'on_event', ...$args ];
	}
}

class Inline_Listener {
	public function handle( mixed ...$args ): void {
		$_SERVER['__queued_listener'][] = [ 'inline', ...$args ];
	}
}

class Conditional_Listener implements Can_Queue {
	public function should_queue( string $value ): bool {
		return 'skip' !== $value;
	}

	public function handle( mixed ...$args ): void {
		$_SERVER['__queued_listener'][] = [ 'conditional', ...$args ];
	}
}

class Delayed_Listener implements Can_Queue {
	public string $queue = 'listeners';

	public int $delay = 60;

	public int $tries = 3;

	public int $retry_backoff = 30;

	public int $timeout = 120;

	public function handle( mixed ...$args ): void {
		$_SERVER['__queued_listener'][] = [ 'delayed', ...$args ];
	}
}
