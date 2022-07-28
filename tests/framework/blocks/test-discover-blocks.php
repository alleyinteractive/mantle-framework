<?php
namespace Mantle\Tests\Framework\Tests;

use Mantle\Framework\Blocks\Discover_Blocks;
use Mantle\Support\Environment;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Tests\Framework\Blocks\Fixtures\Example_Block;

class Test_Discover_Blocks extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// Mock a true Mantle application.
		Environment::get_repository()->set( 'APP_NAMESPACE', 'Mantle\\Tests' );
		$this->app->set_app_path( dirname( __DIR__, 2 ) );
	}

	protected function tearDown(): void {
		Environment::get_repository()->clear( 'APP_NAMESPACE' );
		$this->app->set_app_path( $this->app->get_base_path( 'app' ) );
	}

	public function testBlocksAreFound() {
		$this->assertEquals( 'Mantle\\Tests', $this->app->get_namespace() );

		$expected = [
			Example_Block::class,
		];

		$found_blocks = Discover_Blocks::within(
			__DIR__ . '/fixtures',
			getcwd(),
		);

		$this->assertSame( $expected, $found_blocks );
	}
}
