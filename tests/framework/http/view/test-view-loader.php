<?php
namespace Mantle\Tests\Framework\Http\View;

use Mantle\Framework\Facade\View_Loader;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_View_Loader extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		View_Loader::clear_paths();
		View_Loader::set_default_paths();
		View_Loader::add_path( MANTLE_PHPUNIT_TEMPLATE_PATH, 'unit-test' );
		View_Loader::add_path( MANTLE_PHPUNIT_TEMPLATE_PATH . '/view-loader/alias' , 'view-loader-alias' );

		$_SERVER['__view_loaded'] = false;
	}

	public function test_loading_view() {
		View_Loader::load( 'view-loader/view-loader' );

		$this->assertTrue( $_SERVER['__view_loaded'] );
	}

	/**
	 * Allow an alias to define the specific view location to load from.
	 */
	public function test_loading_view_alias() {
		View_Loader::load( '@view-loader-alias/view-loader' );

		$this->assertEquals( 'alias', $_SERVER['__view_loaded'] );
	}

	/**
	 * Ensure that an alias doesn't fallback to other template parts.
	 */
	public function test_loading_view_alias_fallback() {
		View_Loader::load( '@unit-test/view-loader/alias-specific' );
		$this->assertFalse( $_SERVER['__view_loaded'] );

		View_Loader::load( 'view-loader/view-loader' );
		$this->assertTrue( $_SERVER['__view_loaded'] );

		View_Loader::load( '@view-loader-alias/alias-specific' );
		$this->assertEquals( 'alias', $_SERVER['__view_loaded'] );
	}
}
