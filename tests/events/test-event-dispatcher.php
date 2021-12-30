<?php
namespace Mantle\Tests\Events;

use Mantle\Events\Dispatcher;
use Mantle\Container\Container;
use Mockery as m;

class Test_Event_Dispatcher extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	public function testBasicEventExecution() {
		unset( $_SERVER['__event.test'] );
		$d = new Dispatcher();
		$d->listen(
			__METHOD__,
			function ( $foo ) {
				$_SERVER['__event.test'] = $foo;
			}
		);
		$d->dispatch( __METHOD__, [ 'bar' ] );

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
		$this->assertEquals( 'baz', $d->dispatch( __METHOD__, [ 'foo', 'bar' ] ) );
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
		$d->dispatch( __METHOD__, [ 'foo', 'bar' ] );
	}
}
