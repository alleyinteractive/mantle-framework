<?php

namespace Mantle\Tests\Framework;

use Mantle\Framework\Alias_Loader;
use PHPUnit\Framework\TestCase;

class AliasLoaderTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		Alias_Loader::set_instance( null );
	}

	public function testLoaderCanBeCreatedAndRegisteredOnce(): void {
		$loader = Alias_Loader::get_instance( [ 'foo' => 'bar' ] );

		$this->assertSame( [ 'foo' => 'bar' ], $loader->get_aliases() );
		$this->assertFalse( $loader->is_registered() );
		$loader->register();

		$this->assertTrue( $loader->is_registered() );
	}

	public function testget_instanceCreatesOneInstance(): void {
		$loader = Alias_Loader::get_instance( [ 'foo' => 'bar' ] );
		$this->assertSame( $loader, Alias_Loader::get_instance() );
	}

	public function testLoaderCanBeCreatedAndRegisteredMergingAliases(): void {
		$loader = Alias_Loader::get_instance( [ 'foo' => 'bar' ] );
		$this->assertSame( [ 'foo' => 'bar' ], $loader->get_aliases() );

		$loader = Alias_Loader::get_instance( [ 'foo2' => 'bar2' ] );
		$this->assertEquals(
			[
				'foo2' => 'bar2',
				'foo'  => 'bar',
			],
			$loader->get_aliases()
		);

		// override keys
		$loader = Alias_Loader::get_instance( [ 'foo' => 'baz' ] );
		$this->assertEquals(
			[
				'foo2' => 'bar2',
				'foo'  => 'baz',
			],
			$loader->get_aliases()
		);
	}

	public function testLoaderCanAliasAndLoadClasses(): void {
		$loader = Alias_Loader::get_instance( [ 'some_alias_foo_bar' => FoundationAlias_LoaderStub::class ] );

		$loader->load( 'some_alias_foo_bar' );

		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, new \some_alias_foo_bar() ); // @phpstan-ignore class.notFound (alias created at runtime)

		$result2 = $loader->load( 'bar' ); // @phpstan-ignore method.void (asserts load() returns nothing)
		$this->assertNull( $result2 ); // @phpstan-ignore method.alreadyNarrowedType (asserts load() returns nothing)
	}

	public function testAliasMethodRegistersLoadableAlias(): void {
		$loader = Alias_Loader::get_instance();
		$loader->alias( FoundationAlias_LoaderStub::class, 'some_alias_from_alias_method' );

		$this->assertSame( FoundationAlias_LoaderStub::class, $loader->get_aliases()['some_alias_from_alias_method'] ?? null );

		$loader->load( 'some_alias_from_alias_method' );

		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, new \some_alias_from_alias_method() ); // @phpstan-ignore class.notFound (alias created at runtime)
	}

	public function testSetAlias(): void {
		$loader = Alias_Loader::get_instance();
		$loader->set_aliases( [ 'some_alias_foo' => FoundationAlias_LoaderStub::class ] );

		$loader->load( 'some_alias_foo' );

		$fooObj = new \some_alias_foo(); // @phpstan-ignore class.notFound (alias created at runtime)
		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, $fooObj );
	}
}

class FoundationAlias_LoaderStub {

}
