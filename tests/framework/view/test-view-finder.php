<?php
namespace Mantle\Tests\Framework\View;

use Mantle\Facade\View_Loader;
use Mantle\Testing\Framework_Test_Case;

class Test_View_Finder extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		View_Loader::clear_paths();
		View_Loader::set_default_paths();
		View_Loader::add_path( MANTLE_PHPUNIT_TEMPLATE_PATH, 'unit-test' );
		View_Loader::add_path( MANTLE_PHPUNIT_TEMPLATE_PATH . '/view-loader/alias' , 'view-loader-alias' );

		$_SERVER['__view_loaded'] = false;
	}

	public function test_loading_view() {
		$this->load_view( 'view-loader/view-loader' );

		$this->assertTrue( $_SERVER['__view_loaded'] );
	}

	/**
	 * Allow an alias to define the specific view location to load from.
	 */
	public function test_loading_view_alias() {
		$this->load_view( '@view-loader-alias/view-loader' );

		$this->assertEquals( 'alias', $_SERVER['__view_loaded'] );
	}

	/**
	 * Ensure that an alias doesn't fallback to other template parts.
	 */
	public function test_loading_view_alias_fallback() {
		$this->load_view( '@unit-test/view-loader/alias-specific' );
		$this->assertFalse( $_SERVER['__view_loaded'] );

		$this->load_view( 'view-loader/view-loader' );
		$this->assertTrue( $_SERVER['__view_loaded'] );

		$this->load_view( '@view-loader-alias/alias-specific' );
		$this->assertEquals( 'alias', $_SERVER['__view_loaded'] );
	}

	/**
	 * Load the contents of a view.
	 *
	 * @param string $name View to load.
	 */
	protected function load_view( string $name ) {
		try {
			include View_Loader::find( $name );
		} catch ( \Throwable $e ) { }
	}
}
