<?php
namespace Mantle\Tests\Container;

use Mantle\Framework\Container\Binding_Resolution_Exception;
use Mantle\Framework\Container\Container;
use Mantle\Framework\Container\Entry_Not_Found_Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use stdClass;

class Test_Container extends TestCase {
	public function tearDown() {
		Container::set_instance( null );
	}

	public function test_container_singleton() {
		$container = Container::set_instance( new Container() );

		$this->assertSame( $container, Container::getInstance() );

		Container::set_instance( null );

		$container2 = Container::getInstance();

		$this->assertInstanceOf( Container::class, $container2 );
		$this->assertNotSame( $container, $container2 );
	}

	public function test_closure_resolution() {
		$container = new Container();
		$container->bind(
			'name',
			function () {
					return 'Taylor';
			}
		);

		$this->assertSame( 'Taylor', $container->make( 'name' ) );
	}

	public function test_bind_if_doesnt_register_if_service_already_registered() {
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

	public function test_bind_if_does_register_if_service_not_registered_yet() {
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

	public function test_singleton_if_doesnt_register_if_binding_already_registered() {
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

	public function test_singleton_if_does_register_if_binding_not_registered_yet() {
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

	public function test_shared_closure_resolution() {
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

	public function testAutoConcreteResolution() {
		$container = new Container();
		$this->assertInstanceOf( ContainerConcreteStub::class, $container->make( ContainerConcreteStub::class ) );
	}

	public function testSharedConcreteResolution() {
		$container = new Container();
		$container->singleton( ContainerConcreteStub::class );

		$var1 = $container->make( ContainerConcreteStub::class );
		$var2 = $container->make( ContainerConcreteStub::class );
		$this->assertSame( $var1, $var2 );
	}

	public function testAbstractToConcreteResolution() {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$class = $container->make( ContainerDependentStub::class );
		$this->assertInstanceOf( ContainerImplementationStub::class, $class->impl );
	}

	public function testNestedDependencyResolution() {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$class = $container->make( ContainerNestedDependentStub::class );
		$this->assertInstanceOf( ContainerDependentStub::class, $class->inner );
		$this->assertInstanceOf( ContainerImplementationStub::class, $class->inner->impl );
	}

	public function testContainerIsPassedToResolvers() {
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

	public function testArrayAccess() {
		$container             = new Container();
		$container['something'] = function () {
			return 'foo';
		};
		$this->assertTrue( isset( $container['something'] ) );
		$this->assertSame( 'foo', $container['something'] );
		unset( $container['something'] );
		$this->assertFalse( isset( $container['something'] ) );
	}

	public function testAliases() {
		$container       = new Container();
		$container['foo'] = 'bar';
		$container->alias( 'foo', 'baz' );
		$container->alias( 'baz', 'bat' );
		$this->assertSame( 'bar', $container->make( 'foo' ) );
		$this->assertSame( 'bar', $container->make( 'baz' ) );
		$this->assertSame( 'bar', $container->make( 'bat' ) );
	}

	public function testAliasesWithArrayOfParameters() {
		$container = new Container();
		$container->bind(
			'foo',
			function ( $app, $config ) {
					return $config;
			}
		);
		$container->alias( 'foo', 'baz' );
		$this->assertEquals( array( 1, 2, 3 ), $container->make( 'baz', array( 1, 2, 3 ) ) );
	}

	public function testBindingsCanBeOverridden() {
		$container       = new Container();
		$container['foo'] = 'bar';
		$container['foo'] = 'baz';
		$this->assertSame( 'baz', $container['foo'] );
	}

	public function testBindingAnInstanceReturnsTheInstance() {
		$container = new Container();

		$bound    = new stdClass();
		$resolved = $container->instance( 'foo', $bound );

		$this->assertSame( $bound, $resolved );
	}

	public function testBindingAnInstanceAsShared() {
		$container = new Container();
		$bound    = new stdClass();
		$container->instance( 'foo', $bound );
		$object = $container->make( 'foo' );
		$this->assertSame( $bound, $object );
	}

	public function testResolutionOfDefaultParameters() {
		$container = new Container();
		$instance = $container->make( ContainerDefaultValueStub::class );
		$this->assertInstanceOf( ContainerConcreteStub::class, $instance->stub );
		$this->assertSame( 'taylor', $instance->default );
	}

	public function testUnsetRemoveBoundInstances() {
		$container = new Container();
		$container->instance( 'object', new stdClass() );
		unset( $container['object'] );

		$this->assertFalse( $container->bound( 'object' ) );
	}

	public function testBoundInstanceAndAliasCheckViaArrayAccess() {
		$container = new Container();
		$container->instance( 'object', new stdClass() );
		$container->alias( 'object', 'alias' );

		$this->assertTrue( isset( $container['object'] ) );
		$this->assertTrue( isset( $container['alias'] ) );
	}

	public function testReboundListeners() {
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

	public function testReboundListenersOnInstances() {
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

	public function testReboundListenersOnInstancesOnlyFiresIfWasAlreadyBound() {
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

	public function testInternalClassWithDefaultParameters() {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class Mantle\Tests\Container\ContainerMixedPrimitiveStub' );

		$container = new Container();
		$container->make( ContainerMixedPrimitiveStub::class, array() );
	}

	public function testBindingResolutionExceptionMessage() {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Target [Mantle\Tests\Container\IContainerContractStub] is not instantiable.' );

		$container = new Container();
		$container->make( IContainerContractStub::class, array() );
	}

	public function testBindingResolutionExceptionMessageIncludesBuildStack() {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Target [Mantle\Tests\Container\IContainerContractStub] is not instantiable while building [Mantle\Tests\Container\ContainerDependentStub].' );

		$container = new Container();
		$container->make( ContainerDependentStub::class, array() );
	}

	public function testBindingResolutionExceptionMessageWhenClassDoesNotExist() {
		$this->expectException( Binding_Resolution_Exception::class );
		$this->expectExceptionMessage( 'Target class [Foo\Bar\Baz\DummyClass] does not exist.' );

		$container = new Container();
		$container->build( 'Foo\Bar\Baz\DummyClass' );
	}

	public function testforget_instanceForgetsInstance() {
		$container              = new Container();
		$containerConcreteStub = new ContainerConcreteStub();
		$container->instance( ContainerConcreteStub::class, $containerConcreteStub );
		$this->assertTrue( $container->is_shared( ContainerConcreteStub::class ) );
		$container->forget_instance( ContainerConcreteStub::class );
		$this->assertFalse( $container->is_shared( ContainerConcreteStub::class ) );
	}

	public function testforget_instancesForgetsAllInstances() {
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

	public function testContainerFlushFlushesAllBindingsAliasesAndResolvedInstances() {
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

	public function testResolvedResolvesAliasToBindingNameBeforeChecking() {
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

	public function testGetAlias() {
		$container = new Container();
		$container->alias( 'ConcreteStub', 'foo' );
		$this->assertEquals( $container->get_alias( 'foo' ), 'ConcreteStub' );
	}

	public function testItThrowsExceptionWhenAbstractIsSameAsAlias() {
		$this->expectException( 'LogicException' );
		$this->expectExceptionMessage( '[name] is aliased to itself.' );

		$container = new Container();
		$container->alias( 'name', 'name' );
	}

	public function testContainerGetFactory() {
		$container = new Container();
		$container->bind(
			'name',
			function () {
					return 'Taylor';
			}
		);

		$factory = $container->factory( 'name' );
		$this->assertEquals( $container->make( 'name' ), $factory() );
	}

	public function testMakeWithMethodIsAnAliasForMakeMethod() {
		$mock = $this->getMockBuilder( Container::class )
			->setMethods( array( 'make' ) )
			->getMock();

		$mock->expects( $this->once() )
					->method( 'make' )
					->with( ContainerDefaultValueStub::class, array( 'default' => 'laurence' ) )
					->willReturn( new stdClass() );

		$result = $mock->make_with( ContainerDefaultValueStub::class, array( 'default' => 'laurence' ) );

		$this->assertInstanceOf( stdClass::class, $result );
	}

	public function testResolvingWithArrayOfParameters() {
		$container  = new Container();
		$instance = $container->make( ContainerDefaultValueStub::class, array( 'default' => 'adam' ) );
		$this->assertSame( 'adam', $instance->default );

		$instance = $container->make( ContainerDefaultValueStub::class );
		$this->assertSame( 'taylor', $instance->default );

		$container->bind(
			'foo',
			function ( $app, $config ) {
					return $config;
			}
		);

		$this->assertEquals( array( 1, 2, 3 ), $container->make( 'foo', array( 1, 2, 3 ) ) );
	}

	public function testResolvingWithUsingAnInterface() {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerInjectVariableStubWithInterfaceImplementation::class );
		$instance = $container->make( IContainerContractStub::class, array( 'something' => 'laurence' ) );
		$this->assertSame( 'laurence', $instance->something );
	}

	public function testNestedParameterOverride() {
		$container = new Container();
		$container->bind(
			'foo',
			function ( $app, $config ) {
					return $app->make( 'bar', array( 'name' => 'Taylor' ) );
			}
		);
		$container->bind(
			'bar',
			function ( $app, $config ) {
					return $config;
			}
		);

		$this->assertEquals( array( 'name' => 'Taylor' ), $container->make( 'foo', array( 'something' ) ) );
	}

	public function testNestedParametersAreResetForFreshMake() {
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

		$this->assertEquals( array(), $container->make( 'foo', array( 'something' ) ) );
	}

	public function testSingletonBindingsNotRespectedWithMakeParameters() {
		$container = new Container();

		$container->singleton(
			'foo',
			function ( $app, $config ) {
					return $config;
			}
		);

		$this->assertEquals( array( 'name' => 'taylor' ), $container->make( 'foo', array( 'name' => 'taylor' ) ) );
		$this->assertEquals( array( 'name' => 'abigail' ), $container->make( 'foo', array( 'name' => 'abigail' ) ) );
	}

	public function testCanBuildWithoutParameterStackWithNoConstructors() {
		$container = new Container();
		$this->assertInstanceOf( ContainerConcreteStub::class, $container->build( ContainerConcreteStub::class ) );
	}

	public function testCanBuildWithoutParameterStackWithConstructors() {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$this->assertInstanceOf( ContainerDependentStub::class, $container->build( ContainerDependentStub::class ) );
	}

	public function testContainerKnowsEntry() {
		$container = new Container();
		$container->bind( IContainerContractStub::class, ContainerImplementationStub::class );
		$this->assertTrue( $container->has( IContainerContractStub::class ) );
	}

	public function testContainerCanBindAnyWord() {
		$container = new Container();
		$container->bind( 'Taylor', stdClass::class );
		$this->assertInstanceOf( stdClass::class, $container->get( 'Taylor' ) );
	}

	public function testContainerCanDynamicallySetService() {
		$container = new Container();
		$this->assertFalse( isset( $container['name'] ) );
		$container['name'] = 'Taylor';
		$this->assertTrue( isset( $container['name'] ) );
		$this->assertSame( 'Taylor', $container['name'] );
	}

	public function testUnknownEntryThrowsException() {
		$this->expectException( Entry_Not_Found_Exception::class );

		$container = new Container();
		$container->get( 'Taylor' );
	}

	public function testBoundEntriesThrowsContainerExceptionWhenNotResolvable() {
		$this->expectException( ContainerExceptionInterface::class );

		$container = new Container();
		$container->bind( 'Taylor', IContainerContractStub::class );

		$container->get( 'Taylor' );
	}

	public function testContainerCanResolveClasses() {
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

	public $impl;

	public function __construct( IContainerContractStub $impl ) {
		$this->impl = $impl;
	}
}

class ContainerNestedDependentStub {

	public $inner;

	public function __construct( ContainerDependentStub $inner ) {
		$this->inner = $inner;
	}
}

class ContainerDefaultValueStub {

	public $stub;
	public $default;

	public function __construct( ContainerConcreteStub $stub, $default = 'taylor' ) {
		$this->stub    = $stub;
		$this->default = $default;
	}
}

class ContainerMixedPrimitiveStub {

	public $first;
	public $last;
	public $stub;

	public function __construct( $first, ContainerConcreteStub $stub, $last ) {
		$this->stub  = $stub;
		$this->last  = $last;
		$this->first = $first;
	}
}

class ContainerInjectVariableStub {

	public $something;

	public function __construct( ContainerConcreteStub $concrete, $something ) {
		$this->something = $something;
	}
}

class ContainerInjectVariableStubWithInterfaceImplementation implements IContainerContractStub {

	public $something;

	public function __construct( ContainerConcreteStub $concrete, $something ) {
		$this->something = $something;
	}
}
