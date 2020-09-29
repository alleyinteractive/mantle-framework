<?php
namespace Mantle\Tests\Framework\View;

use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Blade_Views extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		$this->app['view.loader']
			->clear_paths()
			->add_path( MANTLE_PHPUNIT_TEMPLATE_PATH . '/blade', 'blade' );
	}

	public function test_basic() {
		$contents = (string) view( '@blade/basic', [ 'name' => 'world' ] );
		$this->assertSame( 'Hello, world.', trim( $contents ) );
	}

	public function test_if_else() {
		$this->assertContains(
			'True!',
			(string) view( '@blade/if-else', [ 'should_if' => true ] ),
		);

		$this->assertContains(
			'False',
			(string) view( '@blade/if-else', [ 'should_if' => false ] ),
		);
	}

	public function test_include() {
		$this->assertContains(
			'child',
			(string) view( '@blade/parent' )
		);
	}
}
