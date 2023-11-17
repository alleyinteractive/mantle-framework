<?php
namespace Mantle\Tests\View;

use Mantle\Testing\Framework_Test_Case;

class Test_File_Engine extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		$this->app['view.loader']
			->add_path( MANTLE_PHPUNIT_TEMPLATE_PATH . '/view', 'unit-test');
	}

	public function test_css_load() {
		$contents = (string) view( '@unit-test/css' );
		$this->assertSame( 'body { color: red; }', trim( $contents ) );
	}

	public function test_html_load() {
		$contents = (string) view( '@unit-test/html' );
		$this->assertSame( '<p>Content here</p>', trim( $contents ) );
	}
}
