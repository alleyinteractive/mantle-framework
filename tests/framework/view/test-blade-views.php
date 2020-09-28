<?php
namespace Mantle\Tests\Framework\View;

use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Blade_Views extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		$this->app['view.loader']
			->clear_paths()
			->add_path( MANTLE_PHPUNIT_TEMPLATE_PATH . '/template-parts/blade' );
	}

	public function test_basic_blade() {
		$contents = (string) view( 'basic', [ 'name' => 'world' ] );
		$this->assertSame( 'Hello world.', $contents );
	}
}
