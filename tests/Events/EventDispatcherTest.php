<?php
namespace Mantle\Tests\Events;

use Mantle\Events\Dispatcher;
use Mantle\Container\Container;
use Mockery as m;
use PHPUnit\Framework\Attributes\Group;

/**
 * TODO: get_listeners
 *
 * @group events
 */
#[Group( 'events' )]
class EventDispatcherTest extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	public function setUp(): void {
		parent::setUp();

		app()->singleton_if( 'events', fn ( $app ) => new Dispatcher( $app ) );
	}

	public function testBasicEventExecution() {
		unset( $_SERVER['__event.test'] );
		$d = new Dispatcher();
		$d->listen(
			__METHOD__,
			function ( $foo ) {
				$_SERVER['__event.test'] = $foo;
			}
		);

		$this->assertTrue( $d->has_listeners( __METHOD__ ) );
		$d->dispatch( __METHOD__, 'bar' );

		$this->assertEquals( 'bar', $_SERVER['__event.test'] );
	}

	public function testContainerResolutionOfEventHandlers() {
		$d = new Dispatcher( $container = m::mock( Container::class ) );
		$container
			->shouldReceive( 'make' )
			->once()
			->with( 'FooHandler' )
			->andReturn( $handler = m::mock( stdClass::class ) );

		$handler
			->shouldReceive( 'onFooEvent' )
			->once()
			->with( 'foo', 'bar' )
			->andReturn( 'baz' );

		$d->listen( __METHOD__, 'FooHandler@onFooEvent' );

		$this->assertTrue( $d->has_listeners( __METHOD__ ) );

		$this->assertEquals( 'baz', $d->dispatch( __METHOD__, 'foo', 'bar' ) );
	}

	public function testContainerResolutionOfEventHandlersWithDefaultMethods() {
		$d = new Dispatcher( $container = m::mock( Container::class ) );
		$container
			->shouldReceive( 'make' )
			->once()
			->with( 'FooHandler' )
			->andReturn( $handler = m::mock( stdClass::class ) );

		$handler
			->shouldReceive( 'handle' )
			->once()
			->with( 'foo', 'bar' );

		$d->listen( __METHOD__, 'FooHandler' );

		$this->assertTrue( $d->has_listeners( __METHOD__ ) );

		$d->dispatch( __METHOD__, 'foo', 'bar' );
	}

	public function test_typehinted_event_callback_isolated() {
		$_SERVER['__event_run'] = false;

		$d = new Dispatcher( app() );

		$d->listen(
			Example_Event::class,
			fn ( Example_Event $e ) => $_SERVER['__event_run'] = true
		);

		$d->dispatch( new Example_Event() );

		$this->assertTrue( $_SERVER['__event_run'] );
	}

	public function test_typehinted_event_callback() {
		$_SERVER['__event_run'] = false;

		app( 'events' )->listen(
			Example_Event::class,
			fn ( Example_Event $e ) => $_SERVER['__event_run'] = true
		);

		app( 'events' )->dispatch( new Example_Event() );

		$this->assertTrue( $_SERVER['__event_run'] );
	}

	public function test_multiple_void_listeners_receive_same_event_object() {
		$events = app( 'events' );
		$seen   = [];

		$events->listen( Example_Event::class, function ( Example_Event $e ) use ( &$seen ): void { $seen[] = spl_object_id( $e ); } );
		$events->listen( Example_Event::class, function ( Example_Event $e ) use ( &$seen ) { $seen[] = spl_object_id( $e ); } );
		$events->listen( Example_Event::class, Example_Void_Listener::class );

		$event = new Example_Event();

		$events->dispatch( $event );

		$this->assertSame( [ spl_object_id( $event ), spl_object_id( $event ) ], $seen );
		$this->assertSame( spl_object_id( $event ), Example_Void_Listener::$seen );
	}

	public function test_void_listener_does_not_clobber_payload_for_later_listeners() {
		$events = app( 'events' );
		$seen   = [];

		$events->listen( __FUNCTION__, function ( $value ) use ( &$seen ): void { $seen[] = $value; } );
		$events->listen( __FUNCTION__, function ( $value ) use ( &$seen ) { $seen[] = $value; return $value; } );

		$this->assertSame( 'hello', $events->dispatch( __FUNCTION__, 'hello' ) );
		$this->assertSame( [ 'hello', 'hello' ], $seen );
	}

	public function test_it_can_forget_a_specific_listener() {
		$events   = app( 'events' );
		$listener = fn ( $value ) => 'modified';

		$events->listen( __FUNCTION__, $listener );
		$events->listen( __FUNCTION__, Example_Void_Listener::class );

		$this->assertTrue( $events->has_listeners( __FUNCTION__ ) );

		$events->forget( __FUNCTION__, $listener );
		$events->forget( __FUNCTION__, Example_Void_Listener::class );

		$this->assertFalse( $events->has_listeners( __FUNCTION__ ) );
		$this->assertSame( 'original', $events->dispatch( __FUNCTION__, 'original' ) );
	}

	public function test_dispatch_string_event_name() {
		$events = app( 'events' );

		$events->listen(
			__FUNCTION__,
			fn () => $_SERVER['__event_run'] = true
		);

		$events->dispatch( __FUNCTION__ );

		$this->assertTrue( $_SERVER['__event_run'] );
	}

	public function test_dispatch_string_event_name_with_payload() {
		$events = app( 'events' );

		$events->listen(
			__FUNCTION__,
			fn ( $payload ) => $_SERVER['__event_run'] = $payload
		);

		$events->dispatch( __FUNCTION__, 'foo' );

		$this->assertEquals( 'foo', $_SERVER['__event_run'] );
	}

	public function test_dispatch_string_event_name_with_single_non_array_payload() {
		$events = app( 'events' );

		$events->listen(
			__FUNCTION__,
			fn ( $payload ) => $_SERVER['__event_run'] = $payload
		);

		$events->dispatch( __FUNCTION__, 'foo' );

		$this->assertEquals( 'foo', $_SERVER['__event_run'] );
	}

	public function test_dispatch_string_event_name_with_multiple_payloads() {
		$events = app( 'events' );

		$events->listen(
			__FUNCTION__,
			fn ( ...$args ) => $_SERVER['__event_run'] = $args,
		);

		$events->dispatch( __FUNCTION__, 'foo', 'bar' );

		$this->assertEquals( [ 'foo', 'bar' ], $_SERVER['__event_run'] );
	}

	public function test_it_can_forget_events(): void {
		$dispatcher = new Dispatcher();

		$dispatcher->listen( 'event_name', function () {
			return 'event_name';
		} );

		$dispatcher->forget( 'event_name' );

		$this->assertFalse( $dispatcher->has_listeners( 'event_name' ) );
	}

	public function test_it_can_forget_wildcard_events(): void {
		$dispatcher = new Dispatcher();

		$dispatcher->listen( 'event:*', function () {
			return 'event_name';
		} );

		$this->assertTrue( $dispatcher->has_listeners( 'event:name' ) );
		$this->assertTrue( $dispatcher->has_listeners( 'event:another' ) );

		$dispatcher->forget( 'event:*' );

		$this->assertFalse( $dispatcher->has_listeners( 'event:name' ) );
	}

	public function test_it_cannot_dispatch_object_event_with_payload(): void {
		$dispatcher = new Dispatcher();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'You cannot pass payload to an object event.' );

		$dispatcher->dispatch( new Example_Event(), 'foo' );
	}
}

class Example_Event {

}

class Example_Void_Listener {
	public static ?int $seen = null;

	public function handle( $event ): void {
		static::$seen = is_object( $event ) ? spl_object_id( $event ) : null;
	}
}
