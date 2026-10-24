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

		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, $this->make_instance( 'some_alias_foo_bar' ) );

		$loader->load( 'bar' );
		$this->assertFalse( class_exists( 'bar', false ) );
	}

	public function testAliasMethodRegistersLoadableAlias(): void {
		$loader = Alias_Loader::get_instance();
		$loader->alias( FoundationAlias_LoaderStub::class, 'some_alias_from_alias_method' );

		$this->assertSame( FoundationAlias_LoaderStub::class, $loader->get_aliases()['some_alias_from_alias_method'] ?? null );

		$loader->load( 'some_alias_from_alias_method' );

		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, $this->make_instance( 'some_alias_from_alias_method' ) );
	}

	public function testSetAlias(): void {
		$loader = Alias_Loader::get_instance();
		$loader->set_aliases( [ 'some_alias_foo' => FoundationAlias_LoaderStub::class ] );

		$loader->load( 'some_alias_foo' );

		$fooObj = $this->make_instance( 'some_alias_foo' );
		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, $fooObj );
	}

	/**
	 * Instantiate a class by name, resolving it through the registered autoloaders.
	 *
	 * @param string $class_name Class name or alias to instantiate.
	 */
	protected function make_instance( string $class_name ): object {
		return new $class_name();
	}
}

class FoundationAlias_LoaderStub {

}
