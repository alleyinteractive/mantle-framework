<?php

namespace Mantle\Tests\Framework;

use Mantle\Framework\Alias_Loader;
use PHPUnit\Framework\TestCase;

class AliasLoaderTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		Alias_Loader::set_instance( null );
	}

	public function testLoaderCanBeCreatedAndRegisteredOnce() {
		$loader = Alias_Loader::get_instance( [ 'foo' => 'bar' ] );

		$this->assertEquals( [ 'foo' => 'bar' ], $loader->get_aliases() );
		$this->assertFalse( $loader->is_registered() );
		$loader->register();

		$this->assertTrue( $loader->is_registered() );
	}

	public function testget_instanceCreatesOneInstance() {
		$loader = Alias_Loader::get_instance( [ 'foo' => 'bar' ] );
		$this->assertSame( $loader, Alias_Loader::get_instance() );
	}

	public function testLoaderCanBeCreatedAndRegisteredMergingAliases() {
		$loader = Alias_Loader::get_instance( [ 'foo' => 'bar' ] );
		$this->assertEquals( [ 'foo' => 'bar' ], $loader->get_aliases() );

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

	public function testLoaderCanAliasAndLoadClasses() {
		$loader = Alias_Loader::get_instance( [ 'some_alias_foo_bar' => FoundationAlias_LoaderStub::class ] );

		$loader->load( 'some_alias_foo_bar' );

		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, new \some_alias_foo_bar() );

		$result2 = $loader->load( 'bar' );
		$this->assertNull( $result2 );
	}

	public function testSetAlias() {
		$loader = Alias_Loader::get_instance();
		$loader->set_aliases( [ 'some_alias_foo' => FoundationAlias_LoaderStub::class ] );

		$loader->load( 'some_alias_foo' );

		$fooObj = new \some_alias_foo();
		$this->assertInstanceOf( FoundationAlias_LoaderStub::class, $fooObj );
	}
}

class FoundationAlias_LoaderStub {

}
