<?php
namespace Mantle\Tests\Console\Events;

use Mantle\Console\Events\Lightweight_Event_Dispatcher;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group( 'events' )]
class LightweightEventDispatcherTest extends TestCase {
	public function test_listen_events(): void {
		$dispatcher = new Lightweight_Event_Dispatcher();

		$listener = [];

		$dispatcher->listen( 'event_name', function( ...$args ) use ( &$listener ) {
			$listener[] = [ 'event_name', ...$args ];
		} );

		$dispatcher->listen( [ 'event_name_2', 'event_name_3' ], function( ...$args ) use ( &$listener ) {
			$listener[] = [ 'multi_listener', ...$args ];
		} );

		$this->assertTrue( $dispatcher->has_listeners( 'event_name' ) );
		$this->assertTrue( $dispatcher->has_listeners( 'event_name_2' ) );
		$this->assertTrue( $dispatcher->has_listeners( 'event_name_3' ) );

		$dispatcher->dispatch( 'event_name' );
		$dispatcher->dispatch( 'event_name', 'arg1', 'arg2' );

		$this->assertEquals(
			[
				[ 'event_name' ],
				[ 'event_name', 'arg1', 'arg2' ],
			],
			$listener
		);
	}

	public function test_it_can_listen_for_wildcard_events(): void {
		$dispatcher = new Lightweight_Event_Dispatcher();

		$listener = [];

		$dispatcher->listen( 'event:*', function( ...$args ) use ( &$listener ) {
			$listener[] = [ 'wildcard_listener', ...$args ];
		} );

		$dispatcher->has_listeners( 'event:name' );

		$dispatcher->dispatch( 'event:name' );
		$dispatcher->dispatch( 'event:name', 'arg1', 'arg2' );

		$this->assertEquals(
			[
				[ 'wildcard_listener' ],
				[ 'wildcard_listener', 'arg1', 'arg2' ],
			],
			$listener
		);
	}

	public function test_it_can_forget_events(): void {
		$dispatcher = new Lightweight_Event_Dispatcher();

		$dispatcher->listen( 'event_name', function() {
			return 'event_name';
		} );

		$dispatcher->forget( 'event_name' );

		$this->assertFalse( $dispatcher->has_listeners( 'event_name' ) );
	}

	public function test_it_can_forget_wildcard_events(): void {
		$dispatcher = new Lightweight_Event_Dispatcher();

		$dispatcher->listen( 'event:*', function() {
			return 'event_name';
		} );

		$this->assertTrue( $dispatcher->has_listeners( 'event:name' ) );
		$this->assertTrue( $dispatcher->has_listeners( 'event:another' ) );

		$dispatcher->forget( 'event:*' );

		$this->assertFalse( $dispatcher->has_listeners( 'event:name' ) );
	}

	public function test_it_can_filter_a_value(): void {
		$dispatcher = new Lightweight_Event_Dispatcher();

		$dispatcher->listen( 'event_name', function( int $value ) {
			return $value + 1;
		} );

		$this->assertEquals( '2', $dispatcher->dispatch( 'event_name', 1 ) );
	}

	public function test_it_can_listen_and_dispatch_class_events(): void {
		$dispatcher = new Lightweight_Event_Dispatcher();

		$listener = [];

		$dispatcher->listen( Example_Lightweight_Event::class, function( Example_Lightweight_Event $event ) use ( &$listener ) {
			$listener[] = $event->name;
		} );

		$dispatcher->dispatch( new Example_Lightweight_Event() );
		$dispatcher->dispatch( new Example_Lightweight_Event( 'test' ) );

		$this->assertEquals( [ 'example', 'test' ], $listener );
	}

	public function test_it_cannot_dispatch_object_event_with_payload(): void {
		$dispatcher = new Lightweight_Event_Dispatcher();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'You cannot pass payload to an object event.' );

		$dispatcher->dispatch( new Example_Lightweight_Event(), 'foo' );
	}
}

class Example_Lightweight_Event {
	public function __construct( public string $name = 'example' ) {}
}
