<?php

namespace Mantle\Tests\Container;

use Closure;
use Mantle\Framework\Container\Container;
use Mantle\Framework\Container\Binding_Resolution_Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;

class Test_Container_Call extends TestCase {

	public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException() {
		$this->expectException( ReflectionException::class );
		$this->expectExceptionMessage( 'Function ContainerTestCallStub() does not exist' );

		$container = new Container();
		$container->call( 'ContainerTestCallStub' );
	}

	public function testCallWithAtSignBasedClassReferences() {
		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class . '@work', array( 'foo', 'bar' ) );
		$this->assertEquals( array( 'foo', 'bar' ), $result );

		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class . '@inject' );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );

		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class . '@inject', array( 'default' => 'foo' ) );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'foo', $result[1] );

		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class, array( 'foo', 'bar' ), 'work' );
		$this->assertEquals( array( 'foo', 'bar' ), $result );
	}

	public function testCallWithCallableArray() {
		$container = new Container();
		$stub      = new ContainerTestCallStub();
		$result    = $container->call( array( $stub, 'work' ), array( 'foo', 'bar' ) );
		$this->assertEquals( array( 'foo', 'bar' ), $result );
	}

	public function testCallWithStaticMethodNameString() {
		$container = new Container();
		$result    = $container->call( 'Mantle\Tests\Container\ContainerStaticMethodStub::inject' );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testCallWithGlobalMethodName() {
		$container = new Container();
		$result    = $container->call( 'Mantle\Tests\Container\containerTestInject' );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testCallWithBound_Method() {
		$container = new Container();
		$container->bind_method(
			ContainerTestCallStub::class . '@unresolvable',
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( ContainerTestCallStub::class . '@unresolvable' );
		$this->assertEquals( array( 'foo', 'bar' ), $result );

		$container = new Container();
		$container->bind_method(
			ContainerTestCallStub::class . '@unresolvable',
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( array( new ContainerTestCallStub(), 'unresolvable' ) );
		$this->assertEquals( array( 'foo', 'bar' ), $result );

		$container = new Container();
		$result    = $container->call(
			array( new ContainerTestCallStub(), 'inject' ),
			array(
				'_stub'   => 'foo',
				'default' => 'bar',
			)
		);
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'bar', $result[1] );

		$container = new Container();
		$result    = $container->call( array( new ContainerTestCallStub(), 'inject' ), array( '_stub' => 'foo' ) );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testBindMethodAcceptsAnArray() {
		$container = new Container();
		$container->bind_method(
			array( ContainerTestCallStub::class, 'unresolvable' ),
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( ContainerTestCallStub::class . '@unresolvable' );
		$this->assertEquals( array( 'foo', 'bar' ), $result );

		$container = new Container();
		$container->bind_method(
			array( ContainerTestCallStub::class, 'unresolvable' ),
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( array( new ContainerTestCallStub(), 'unresolvable' ) );
		$this->assertEquals( array( 'foo', 'bar' ), $result );
	}

	public function testClosureCallWithInjectedDependency() {
		$container = new Container();
		$container->call(
			function ( ContainerCallConcreteStub $stub ) {
			},
			array( 'foo' => 'bar' )
		);

		$container->call(
			function ( ContainerCallConcreteStub $stub ) {
			},
			array(
				'foo'  => 'bar',
				'stub' => new ContainerCallConcreteStub(),
			)
		);
	}

	public function testCallWithDependencies() {
		$container = new Container();
		$result    = $container->call(
			function ( stdClass $foo, $bar = array() ) {
				return func_get_args();
			}
		);

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertEquals( array(), $result[1] );

		$result = $container->call(
			function ( stdClass $foo, $bar = array() ) {
				return func_get_args();
			},
			array( 'bar' => 'taylor' )
		);

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );

		$stub   = new ContainerCallConcreteStub();
		$result = $container->call(
			function ( stdClass $foo, ContainerCallConcreteStub $bar ) {
				return func_get_args();
			},
			array( ContainerCallConcreteStub::class => $stub )
		);

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertSame( $stub, $result[1] );

		/*
		 * Wrap a function...
		 */
		$result = $container->wrap(
			function ( stdClass $foo, $bar = array() ) {
				return func_get_args();
			},
			array( 'bar' => 'taylor' )
		);

		$this->assertInstanceOf( Closure::class, $result );
		$result = $result();

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testCallWithCallableObject() {
		$container = new Container();
		$callable  = new ContainerCallCallableStub();
		$result    = $container->call( $callable );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'jeffrey', $result[1] );
	}

	public function testCallWithoutRequiredParamsThrowsException() {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Mantle\Tests\Container\ContainerTestCallStub' );

		$container = new Container();
		$container->call( ContainerTestCallStub::class . '@unresolvable' );
	}

	public function testCallWithoutRequiredParamsOnClosureThrowsException() {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Mantle\Tests\Container\Test_Container_Call' );

		$container = new Container();
		$foo       = $container->call(
			function ( $foo, $bar = 'default' ) {
				return $foo;
			}
		);
	}
}

class ContainerTestCallStub {

	public function work() {
		return func_get_args();
	}

	public function inject( ContainerCallConcreteStub $stub, $default = 'taylor' ) {
		return func_get_args();
	}

	public function unresolvable( $foo, $bar ) {
		return func_get_args();
	}
}

class ContainerCallConcreteStub {

}

function containerTestInject( ContainerCallConcreteStub $stub, $default = 'taylor' ) {
	return func_get_args();
}

class ContainerStaticMethodStub {

	public static function inject( ContainerCallConcreteStub $stub, $default = 'taylor' ) {
		return func_get_args();
	}
}

class ContainerCallCallableStub {

	public function __invoke( ContainerCallConcreteStub $stub, $default = 'jeffrey' ) {
		return func_get_args();
	}
}
