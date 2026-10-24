<?php

namespace Mantle\Tests\Container;

use Closure;
use Error;
use Mantle\Container\Container;
use Mantle\Container\Binding_Resolution_Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;

class ContainerCallTest extends TestCase {

	public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException(): void {
		$this->expectException( Error::class );
		$this->expectExceptionMessage( 'Call to undefined function ContainerTestCallStub()' );

		$container = new Container();
		$container->call( 'ContainerTestCallStub' );
	}

	public function testCallClosureWithUnresolvableScalarThrowsBindingException(): void {
		$this->expectException( Binding_Resolution_Exception::class );

		( new Container() )->call( fn ( int $a ) => $a );
	}

	public function testCallWithAtSignBasedClassReferences(): void {
		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class . '@work', array( 'foo', 'bar' ) );
		$this->assertSame( [ 'foo', 'bar' ], $result );

		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class . '@inject' );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );

		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class . '@inject', [ 'default' => 'foo' ] );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'foo', $result[1] );

		$container = new Container();
		$result    = $container->call( ContainerTestCallStub::class, [ 'foo', 'bar' ], 'work' );
		$this->assertSame( [ 'foo', 'bar' ], $result );
	}

	public function testCallWithCallableArray(): void {
		$container = new Container();
		$stub      = new ContainerTestCallStub();
		$result    = $container->call( array( $stub, 'work' ), [ 'foo', 'bar' ] );
		$this->assertSame( [ 'foo', 'bar' ], $result );
	}

	public function testCallWithStaticMethodNameString(): void {
		$container = new Container();
		$result    = $container->call( 'Mantle\Tests\Container\ContainerStaticMethodStub::inject' );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testCallWithGlobalMethodName(): void {
		$container = new Container();
		$result    = $container->call( 'Mantle\Tests\Container\containerTestInject' );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testCallWithBound_Method(): void {
		$container = new Container();
		$container->bind_method(
			ContainerTestCallStub::class . '@unresolvable',
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( ContainerTestCallStub::class . '@unresolvable' );
		$this->assertSame( [ 'foo', 'bar' ], $result );

		$container = new Container();
		$container->bind_method(
			ContainerTestCallStub::class . '@unresolvable',
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( array( new ContainerTestCallStub(), 'unresolvable' ) );
		$this->assertSame( array( 'foo', 'bar' ), $result );

		$container = new Container();
		$result    = $container->call(
			[ new ContainerTestCallStub(), 'inject' ],
			[
				'_stub'   => 'foo',
				'default' => 'bar',
			]
		);
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'bar', $result[1] );

		$container = new Container();
		$result    = $container->call( [ new ContainerTestCallStub(), 'inject' ], [ '_stub' => 'foo' ] );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testBindMethodAcceptsAnArray(): void {
		$container = new Container();
		$container->bind_method(
			[ ContainerTestCallStub::class, 'unresolvable' ],
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( ContainerTestCallStub::class . '@unresolvable' );
		$this->assertSame( [ 'foo', 'bar' ], $result );

		$container = new Container();
		$container->bind_method(
			[ ContainerTestCallStub::class, 'unresolvable' ],
			function ( $stub ) {
				return $stub->unresolvable( 'foo', 'bar' );
			}
		);
		$result = $container->call( array( new ContainerTestCallStub(), 'unresolvable' ) );
		$this->assertSame( [ 'foo', 'bar' ], $result );
	}

	public function testCallWithDependencies(): void {
		$container = new Container();
		$result    = $container->call(
			function ( stdClass $foo, $bar = [] ) {
				return func_get_args();
			}
		);

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertSame( [], $result[1] );

		$result = $container->call(
			function ( stdClass $foo, $bar = [] ) {
				return func_get_args();
			},
			[ 'bar' => 'taylor' ]
		);

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );

		$stub   = new ContainerCallConcreteStub();
		$result = $container->call(
			function ( stdClass $foo, ContainerCallConcreteStub $bar ) {
				return func_get_args();
			},
			[ ContainerCallConcreteStub::class => $stub ]
		);

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertSame( $stub, $result[1] );

		/*
		 * Wrap a function...
		 */
		$result = $container->wrap(
			function ( stdClass $foo, $bar = [] ) {
				return func_get_args();
			},
			[ 'bar' => 'taylor' ]
		);

		$result = $result();

		$this->assertInstanceOf( stdClass::class, $result[0] );
		$this->assertSame( 'taylor', $result[1] );
	}

	public function testCallWithCallableObject(): void {
		$container = new Container();
		$callable  = new ContainerCallCallableStub();
		$result    = $container->call( $callable );
		$this->assertInstanceOf( ContainerCallConcreteStub::class, $result[0] );
		$this->assertSame( 'jeffrey', $result[1] );
	}

	public function testCallWithoutRequiredParamsThrowsException(): void {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Mantle\Tests\Container\ContainerTestCallStub' );

		$container = new Container();
		$container->call( ContainerTestCallStub::class . '@unresolvable' );
	}

	public function testCallWithoutRequiredParamsOnClosureThrowsException(): void {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Mantle\Tests\Container\ContainerCallTest' );

		$container = new Container();
		$foo       = $container->call(
			function ( $foo, $bar = 'default' ) {
				return $foo;
			}
		);
	}
}

class ContainerTestCallStub {

	/**
	 * @return array<int, mixed>
	 */
	public function work(): array {
		return func_get_args();
	}

	/**
	 * @return array<int, mixed>
	 */
	public function inject( ContainerCallConcreteStub $stub, string $default = 'taylor' ): array {
		return func_get_args();
	}

	/**
	 * @param  mixed $foo Unresolvable parameter.
	 * @param  mixed $bar Unresolvable parameter.
	 * @return array<int, mixed>
	 */
	public function unresolvable( $foo, $bar ): array {
		return func_get_args();
	}
}

class ContainerCallConcreteStub {

}

/**
 * @return array<int, mixed>
 */
function containerTestInject( ContainerCallConcreteStub $stub, string $default = 'taylor' ): array {
	return func_get_args();
}

class ContainerStaticMethodStub {

	/**
	 * @return array<int, mixed>
	 */
	public static function inject( ContainerCallConcreteStub $stub, string $default = 'taylor' ): array {
		return func_get_args();
	}
}

class ContainerCallCallableStub {

	/**
	 * @return array<int, mixed>
	 */
	public function __invoke( ContainerCallConcreteStub $stub, string $default = 'jeffrey' ): array {
		return func_get_args();
	}
}
