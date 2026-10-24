<?php
namespace Mantle\Tests\Container;

use Mantle\Container\Binding_Resolution_Exception;
use Mantle\Container\Container;
use Mantle\Container\Entry_Not_Found_Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use stdClass;

class ContainerTest extends TestCase {
	protected function tearDown(): void {
		Container::set_instance( null );
	}

	public function test_variadic_primitive_constructor(): void {
		$container = new Container();

		$instance = $container->make( ContainerVariadicPrimitiveStub::class );

		$this->assertSame( [], $instance->names );
	}

	public function test_variadic_class_constructor(): void {
		$container = new Container();

		$instance = $container->make( ContainerVariadicClassStub::class );

		$this->assertCount( 1, $instance->stubs );
		$this->assertInstanceOf( ContainerConcreteStub::class, $instance->stubs[0] );
	}

	public function test_container_singleton(): void {
		$container = Container::set_instance( new Container() );

		$this->assertSame( $container, Container::get_instance() );

		Container::set_instance( null );

		$container2 = Container::get_instance();

		$this->assertInstanceOf( Container::class, $container2 );
		$this->assertNotSame( $container, $container2 );
	}

	public function test_closure_resolution(): void {
		$container = new Container();
		$container->bind(
			'name',
			function () {
					return 'Taylor';
			}
		);

		$this->assertSame( 'Taylor', $container->make( 'name' ) );
	}

	public function test_bind_if_doesnt_register_if_service_already_registered(): void {
		$container = new Container();
		$container->bind(
			'name',
			function () {
				return 'Taylor';
			}
		);
		$container->bind_if(
			'name',
			function () {
				return 'Dayle';
			}
		);

		$this->assertSame( 'Taylor', $container->make( 'name' ) );
	}

	public function test_bind_if_does_register_if_service_not_registered_yet(): void {
		$container = new Container();
		$container->bind(
			'surname',
			function () {
				return 'Taylor';
			}
		);
		$container->bind_if(
			'name',
			function () {
				return 'Dayle';
			}
		);

		$this->assertSame( 'Dayle', $container->make( 'name' ) );
	}

	public function test_singleton_if_doesnt_register_if_binding_already_registered(): void {
		$container = new Container();
		$container->singleton(
			'class',
			function () {
				return new \stdClass();
			}
		);
		$firstInstantiation = $container->make( 'class' );
		$container->singleton_if(
			'class',
			function () {
				return new ContainerConcreteStub();
			}
		);
		$secondInstantiation = $container->make( 'class' );
		$this->assertSame( $firstInstantiation, $secondInstantiation );
	}

	public function test_singleton_if_does_register_if_binding_not_registered_yet(): void {
		$container = new Container();
		$container->singleton(
			'class',
			function () {
				return new \stdClass();
			}
		);
		$container->singleton_if(
			'otherClass',
			function () {
				return new ContainerConcreteStub();
			}
		);
		$firstInstantiation  = $container->make( 'otherClass' );
		$secondInstantiation = $container->make( 'otherClass' );
		$this->assertSame( $firstInstantiation, $secondInstantiation );
	}

	public function test_shared_closure_resolution(): void {
		$container = new Container();
		$container->singleton(
			'class',
			function () {
				return new \stdClass();
			}
		);
		$firstInstantiation  = $container->make( 'class' );
		$secondInstantiation = $container->make( 'class' );
		$this->assertSame( $firstInstantiation, $secondInstantiation );
	}

	public function testAutoConcreteResolution(): void {
		$container = new Container();
		$this->assertInstanceOf( ContainerConcreteStub::class, $container->make( ContainerConcreteStub::class ) );
	}

	public function testSharedConcreteResolution(): void {
		$container = new Container();
		$container->singleton( ContainerConcreteStub::class );

		$var1 = $container->make( ContainerConcreteStub::class );
		$var2 = $container->make( ContainerConcreteStub::class );
		$this->assertSame( $var1, $var2 );
	}

	public function testAbstractToConcreteResolution(): void {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$class = $container->make( ContainerDependentStub::class );
		$this->assertInstanceOf( ContainerImplementationStub::class, $class->impl );
	}

	public function testNestedDependencyResolution(): void {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$class = $container->make( ContainerNestedDependentStub::class );
		$this->assertInstanceOf( ContainerDependentStub::class, $class->inner );
		$this->assertInstanceOf( ContainerImplementationStub::class, $class->inner->impl );
	}

	public function testContainerIsPassedToResolvers(): void {
		$container = new Container();
		$container->bind(
			'something',
			function ( $c ) {
					return $c;
			}
		);
		$c = $container->make( 'something' );
		$this->assertSame( $c, $container );
	}

	public function testArrayAccess(): void {
		$container             = new Container();
		$container['something'] = function () {
			return 'foo';
		};
		$this->assertTrue( isset( $container['something'] ) );
		$this->assertSame( 'foo', $container['something'] );
		unset( $container['something'] );
		$this->assertFalse( isset( $container['something'] ) );
	}

	public function testAliases(): void {
		$container       = new Container();
		$container['foo'] = 'bar';
		$container->alias( 'foo', 'baz' );
		$container->alias( 'baz', 'bat' );
		$this->assertSame( 'bar', $container->make( 'foo' ) );
		$this->assertSame( 'bar', $container->make( 'baz' ) );
		$this->assertSame( 'bar', $container->make( 'bat' ) );
	}

	public function testAliasesWithArrayOfParameters(): void {
		$container = new Container();
		$container->bind(
			'foo',
			function ( $app, $config ) {
					return $config;
			}
		);
		$container->alias( 'foo', 'baz' );
		$this->assertSame( [ 1, 2, 3 ], $container->make( 'baz', [ 1, 2, 3 ] ) );
	}

	public function testBindingsCanBeOverridden(): void {
		$container       = new Container();
		$container['foo'] = 'bar';
		$container['foo'] = 'baz';
		$this->assertSame( 'baz', $container['foo'] );
	}

	public function testBindingAnInstanceReturnsTheInstance(): void {
		$container = new Container();

		$bound    = new stdClass();
		$resolved = $container->instance( 'foo', $bound );

		$this->assertSame( $bound, $resolved );
	}

	public function testBindingAnInstanceAsShared(): void {
		$container = new Container();
		$bound    = new stdClass();
		$container->instance( 'foo', $bound );
		$object = $container->make( 'foo' );
		$this->assertSame( $bound, $object );
	}

	public function testResolutionOfDefaultParameters(): void {
		$container = new Container();
		$instance = $container->make( ContainerDefaultValueStub::class );
		$this->assertInstanceOf( ContainerConcreteStub::class, $instance->stub );
		$this->assertSame( 'taylor', $instance->default );
	}

	public function testUnsetRemoveBoundInstances(): void {
		$container = new Container();
		$container->instance( 'object', new stdClass() );
		unset( $container['object'] );

		$this->assertFalse( $container->bound( 'object' ) );
	}

	public function testBoundInstanceAndAliasCheckViaArrayAccess(): void {
		$container = new Container();
		$container->instance( 'object', new stdClass() );
		$container->alias( 'object', 'alias' );

		$this->assertTrue( isset( $container['object'] ) );
		$this->assertTrue( isset( $container['alias'] ) );
	}

	public function testReboundListeners(): void {
		unset( $_SERVER['__test.rebind'] );

		$container = new Container();
		$container->bind(
			'foo',
			function () {
			}
		);
		$container->rebinding(
			'foo',
			function () {
					$_SERVER['__test.rebind'] = true;
			}
		);
		$container->bind(
			'foo',
			function () {
			}
		);

		$this->assertTrue( $_SERVER['__test.rebind'] );
	}

	public function testReboundListenersOnInstances(): void {
		unset( $_SERVER['__test.rebind'] );

		$container = new Container();
		$container->instance(
			'foo',
			function () {
			}
		);
		$container->rebinding(
			'foo',
			function () {
					$_SERVER['__test.rebind'] = true;
			}
		);
		$container->instance(
			'foo',
			function () {
			}
		);

		$this->assertTrue( $_SERVER['__test.rebind'] );
	}

	public function testReboundListenersOnInstancesOnlyFiresIfWasAlreadyBound(): void {
		$_SERVER['__test.rebind'] = false;

		$container = new Container();
		$container->rebinding(
			'foo',
			function () {
					$_SERVER['__test.rebind'] = true;
			}
		);
		$container->instance(
			'foo',
			function () {
			}
		);

		$this->assertFalse( $_SERVER['__test.rebind'] );
	}

	public function testInternalClassWithDefaultParameters(): void {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class Mantle\Tests\Container\ContainerMixedPrimitiveStub' );

		$container = new Container();
		$container->make( ContainerMixedPrimitiveStub::class, [] );
	}

	public function testBindingResolutionExceptionMessage(): void {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Target [Mantle\Tests\Container\IContainerContractStub] is not instantiable.' );

		$container = new Container();
		$container->make( IContainerContractStub::class, [] );
	}

	public function testBindingResolutionExceptionMessageIncludesBuildStack(): void {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Target [Mantle\Tests\Container\IContainerContractStub] is not instantiable while building [Mantle\Tests\Container\ContainerDependentStub].' );

		$container = new Container();
		$container->make( ContainerDependentStub::class, [] );
	}

	public function testBindingResolutionExceptionMessageWhenClassDoesNotExist(): void {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Target class [Foo\Bar\Baz\DummyClass] does not exist.' );

		$container = new Container();
		$container->build( 'Foo\Bar\Baz\DummyClass' );
	}

	public function testforget_instanceForgetsInstance(): void {
		$container              = new Container();
		$containerConcreteStub = new ContainerConcreteStub();
		$container->instance( ContainerConcreteStub::class, $containerConcreteStub );
		$this->assertTrue( $container->is_shared( ContainerConcreteStub::class ) );
		$container->forget_instance( ContainerConcreteStub::class );
		$this->assertFalse( $container->is_shared( ContainerConcreteStub::class ) );
	}

	public function testforget_instancesForgetsAllInstances(): void {
		$container              = new Container();
		$containerConcreteStub1 = new ContainerConcreteStub();
		$containerConcreteStub2 = new ContainerConcreteStub();
		$containerConcreteStub3 = new ContainerConcreteStub();
		$container->instance( 'Instance1', $containerConcreteStub1 );
		$container->instance( 'Instance2', $containerConcreteStub2 );
		$container->instance( 'Instance3', $containerConcreteStub3 );
		$this->assertTrue( $container->is_shared( 'Instance1' ) );
		$this->assertTrue( $container->is_shared( 'Instance2' ) );
		$this->assertTrue( $container->is_shared( 'Instance3' ) );
		$container->forget_instances();
		$this->assertFalse( $container->is_shared( 'Instance1' ) );
		$this->assertFalse( $container->is_shared( 'Instance2' ) );
		$this->assertFalse( $container->is_shared( 'Instance3' ) );
	}

	public function testContainerFlushFlushesAllBindingsAliasesAndResolvedInstances(): void {
			$container = new Container();
			$container->bind(
				'ConcreteStub',
				function () {
						return new ContainerConcreteStub();
				},
				true
			);
			$container->alias( 'ConcreteStub', 'ContainerConcreteStub' );
			$container->make( 'ConcreteStub' );
			$this->assertTrue( $container->resolved( 'ConcreteStub' ) );
			$this->assertTrue( $container->is_alias( 'ContainerConcreteStub' ) );
			$this->assertArrayHasKey( 'ConcreteStub', $container->get_bindings() );
			$this->assertTrue( $container->is_shared( 'ConcreteStub' ) );
			$container->flush();
			$this->assertFalse( $container->resolved( 'ConcreteStub' ) );
			$this->assertFalse( $container->is_alias( 'ContainerConcreteStub' ) );
			$this->assertEmpty( $container->get_bindings() );
			$this->assertFalse( $container->is_shared( 'ConcreteStub' ) );
	}

	public function testResolvedResolvesAliasToBindingNameBeforeChecking(): void {
		$container = new Container();
		$container->bind(
			'ConcreteStub',
			function () {
					return new ContainerConcreteStub();
			},
			true
		);
		$container->alias( 'ConcreteStub', 'foo' );

		$this->assertFalse( $container->resolved( 'ConcreteStub' ) );
		$this->assertFalse( $container->resolved( 'foo' ) );

		$container->make( 'ConcreteStub' );

		$this->assertTrue( $container->resolved( 'ConcreteStub' ) );
		$this->assertTrue( $container->resolved( 'foo' ) );
	}

	public function testGetAlias(): void {
		$container = new Container();
		$container->alias( 'ConcreteStub', 'foo' );
		$this->assertSame( $container->get_alias( 'foo' ), 'ConcreteStub' );
	}

	public function testItThrowsExceptionWhenAbstractIsSameAsAlias(): void {
		$this->expectException( 'LogicException' );
		$this->expectExceptionMessage( '[name] is aliased to itself.' );

		$container = new Container();
		$container->alias( 'name', 'name' );
	}

	public function testContainerGetFactory(): void {
		$container = new Container();
		$container->bind(
			'name',
			function () {
					return 'Taylor';
			}
		);

		$factory = $container->factory( 'name' );
		$this->assertSame( $container->make( 'name' ), $factory() );
	}

	public function testMakeWithMethodIsAnAliasForMakeMethod(): void {
		$mock = $this->getMockBuilder( Container::class )
			->onlyMethods( [ 'make' ] )
			->getMock();

		$mock->expects( $this->once() )
					->method( 'make' )
					->with( ContainerDefaultValueStub::class, [ 'default' => 'laurence' ] )
					->willReturn( new stdClass() );

		$result = $mock->make_with( ContainerDefaultValueStub::class, [ 'default' => 'laurence' ] );

		$this->assertInstanceOf( stdClass::class, $result );
	}

	public function testResolvingWithArrayOfParameters(): void {
		$container  = new Container();
		$instance = $container->make( ContainerDefaultValueStub::class, [ 'default' => 'adam' ] );
		$this->assertSame( 'adam', $instance->default );

		$instance = $container->make( ContainerDefaultValueStub::class );
		$this->assertSame( 'taylor', $instance->default );

		$container->bind(
			'foo',
			function ( $app, $config ) {
					return $config;
			}
		);

		$this->assertSame( [ 1, 2, 3 ], $container->make( 'foo', [ 1, 2, 3 ] ) );
	}

	public function testResolvingWithUsingAnInterface(): void {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerInjectVariableStubWithInterfaceImplementation::class );
		$instance = $container->make( IContainerContractStub::class, [ 'something' => 'laurence' ] );
		$this->assertSame( 'laurence', $instance->something );
	}

	public function testNestedParameterOverride(): void {
		$container = new Container();
		$container->bind(
			'foo',
			function ( $app, $config ) {
					return $app->make( 'bar', [ 'name' => 'Taylor' ] );
			}
		);
		$container->bind(
			'bar',
			function ( $app, $config ) {
					return $config;
			}
		);

		$this->assertSame( [ 'name' => 'Taylor' ], $container->make( 'foo', [ 'something' ] ) );
	}

	public function testNestedParametersAreResetForFreshMake(): void {
		$container = new Container();

		$container->bind(
			'foo',
			function ( $app, $config ) {
					return $app->make( 'bar' );
			}
		);

		$container->bind(
			'bar',
			function ( $app, $config ) {
					return $config;
			}
		);

		$this->assertSame( [], $container->make( 'foo', [ 'something' ] ) );
	}

	public function testSingletonBindingsNotRespectedWithMakeParameters(): void {
		$container = new Container();

		$container->singleton(
			'foo',
			function ( $app, $config ) {
					return $config;
			}
		);

		$this->assertSame( [ 'name' => 'taylor' ], $container->make( 'foo', [ 'name' => 'taylor' ] ) );
		$this->assertSame( [ 'name' => 'abigail' ], $container->make( 'foo', [ 'name' => 'abigail' ] ) );
	}

	public function testCanBuildWithoutParameterStackWithNoConstructors(): void {
		$container = new Container();
		$this->assertInstanceOf( ContainerConcreteStub::class, $container->build( ContainerConcreteStub::class ) );
	}

	public function testCanBuildWithoutParameterStackWithConstructors(): void {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$this->assertInstanceOf( ContainerDependentStub::class, $container->build( ContainerDependentStub::class ) );
	}

	public function testContainerKnowsEntry(): void {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$this->assertTrue( $container->has( IContainerContractStub::class ) );
	}

	public function testContainerCanBindAnyWord(): void {
		$container = new Container();
		$container->bind( 'Taylor', stdClass::class );
		$this->assertInstanceOf( stdClass::class, $container->get( 'Taylor' ) );
	}

	public function testContainerCanDynamicallySetService(): void {
		$container = new Container();
		$this->assertFalse( isset( $container['name'] ) );
		$container['name'] = 'Taylor';
		$this->assertTrue( isset( $container['name'] ) );
		$this->assertSame( 'Taylor', $container['name'] );
	}

	public function testUnknownEntryThrowsException(): void {
		$this->expectException( Entry_Not_Found_Exception::class );

		$container = new Container();
		$container->get( 'Taylor' );
	}

	public function testBoundEntriesThrowsContainerExceptionWhenNotResolvable(): void {
		$this->expectException( ContainerExceptionInterface::class );

		$container = new Container();
		$container->bind( 'Taylor', IContainerContractStub::class );

		$container->get( 'Taylor' );
	}

	public function testContainerCanResolveClasses(): void {
		$container = new Container();
		$class   = $container->get( ContainerConcreteStub::class );

		$this->assertInstanceOf( ContainerConcreteStub::class, $class );
	}
}


class ContainerConcreteStub {

}

interface IContainerContractStub {

}

class ContainerImplementationStub implements IContainerContractStub {

}

class ContainerImplementationStubTwo implements IContainerContractStub {

}

class ContainerDependentStub {

	public IContainerContractStub $impl;

	public function __construct( IContainerContractStub $impl ) {
		$this->impl = $impl;
	}
}

class ContainerNestedDependentStub {

	public ContainerDependentStub $inner;

	public function __construct( ContainerDependentStub $inner ) {
		$this->inner = $inner;
	}
}

class ContainerDefaultValueStub {

	public ContainerConcreteStub $stub;
	public mixed $default;

	public function __construct( ContainerConcreteStub $stub, $default = 'taylor' ) {
		$this->stub    = $stub;
		$this->default = $default;
	}
}

class ContainerMixedPrimitiveStub {

	public mixed $first;
	public mixed $last;
	public ContainerConcreteStub $stub;

	public function __construct( $first, ContainerConcreteStub $stub, $last ) {
		$this->stub  = $stub;
		$this->last  = $last;
		$this->first = $first;
	}
}

class ContainerInjectVariableStub {

	public mixed $something;

	public function __construct( ContainerConcreteStub $concrete, $something ) {
		$this->something = $something;
	}
}

class ContainerInjectVariableStubWithInterfaceImplementation implements IContainerContractStub {

	public mixed $something;

	public function __construct( ContainerConcreteStub $concrete, $something ) {
		$this->something = $something;
	}
}

class ContainerVariadicPrimitiveStub {
	public array $names;

	public function __construct( string ...$names ) {
		$this->names = $names;
	}
}

class ContainerVariadicClassStub {
	public array $stubs;

	public function __construct( ContainerConcreteStub ...$stubs ) {
		$this->stubs = $stubs;
	}
}
