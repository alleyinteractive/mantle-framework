<?php
namespace Mantle\Tests\Events;

use Mantle\Events\Dispatcher;
use Exception;
use Mantle\Container\Container;
use Mockery as m;

use function Mantle\Framework\Helpers\collect;

class Test_Event_Dispatcher extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	public function testBasicEventExecution() {
		unset( $_SERVER['__event.test'] );
		$d = new Dispatcher();
		$d->listen(
			'foo',
			function ( $foo ) {
				$_SERVER['__event.test'] = $foo;
			}
		);
		$response = $d->dispatch( 'foo', array( 'bar' ) );

		$this->assertEquals( array( null ), $response );
		$this->assertSame( 'bar', $_SERVER['__event.test'] );
	}

	public function testHaltingEventExecution() {
		unset( $_SERVER['__event.test'] );
		$d = new Dispatcher();
		$d->listen(
			'foo',
			function ( $foo ) {
				$this->assertTrue( true );

				return 'here';
			}
		);
		$d->listen(
			'foo',
			function ( $foo ) {
				throw new Exception( 'should not be called' );
			}
		);

		$response = $d->dispatch( 'foo', array( 'bar' ), true );
		$this->assertSame( 'here', $response );

		$response = $d->until( 'foo', array( 'bar' ) );
		$this->assertSame( 'here', $response );
	}

	public function testResponseWhenNoListenersAreSet() {
		$d        = new Dispatcher();
		$response = $d->dispatch( 'foo' );

		$this->assertEquals( array(), $response );

		$response = $d->dispatch( 'foo', array(), true );
		$this->assertNull( $response );
	}

	public function testReturningFalseStopsPropagation() {
		unset( $_SERVER['__event.test'] );
		$d = new Dispatcher();
		$d->listen(
			'foo',
			function ( $foo ) {
				return $foo;
			}
		);

		$d->listen(
			'foo',
			function ( $foo ) {
				$_SERVER['__event.test'] = $foo;

				return false;
			}
		);

		$d->listen(
			'foo',
			function ( $foo ) {
				throw new Exception( 'should not be called' );
			}
		);

		$response = $d->dispatch( 'foo', array( 'bar' ) );

		$this->assertSame( 'bar', $_SERVER['__event.test'] );
		$this->assertEquals( array( 'bar' ), $response );
	}

	public function testReturningFalsyValuesContinuesPropagation() {
		unset( $_SERVER['__event.test'] );
		$d = new Dispatcher();
		$d->listen(
			'foo',
			function () {
				return 0;
			}
		);
		$d->listen(
			'foo',
			function () {
				return array();
			}
		);
		$d->listen(
			'foo',
			function () {
				return '';
			}
		);
		$d->listen(
			'foo',
			function () {
			}
		);

		$response = $d->dispatch( 'foo', array( 'bar' ) );

		$this->assertEquals( array( 0, array(), '', null ), $response );
	}

	public function testContainerResolutionOfEventHandlers() {
		$d = new Dispatcher( $container = m::mock( Container::class ) );
		$container->shouldReceive( 'make' )->once()->with( 'FooHandler' )->andReturn( $handler = m::mock( stdClass::class ) );
		$handler->shouldReceive( 'onFooEvent' )->once()->with( 'foo', 'bar' )->andReturn( 'baz' );
		$d->listen( 'foo', 'FooHandler@onFooEvent' );
		$response = $d->dispatch( 'foo', array( 'foo', 'bar' ) );

		$this->assertEquals( array( 'baz' ), $response );
	}

	public function testContainerResolutionOfEventHandlersWithDefaultMethods() {
		$d = new Dispatcher( $container = m::mock( Container::class ) );
		$container->shouldReceive( 'make' )->once()->with( 'FooHandler' )->andReturn( $handler = m::mock( stdClass::class ) );
		$handler->shouldReceive( 'handle' )->once()->with( 'foo', 'bar' );
		$d->listen( 'foo', 'FooHandler' );
		$d->dispatch( 'foo', array( 'foo', 'bar' ) );
	}
}
