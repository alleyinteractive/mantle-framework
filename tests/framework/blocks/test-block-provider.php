<?php
namespace Mantle\Tests\Framework\Tests;

use Mantle\Application\Application;
use Mantle\Blocks\Discover_Blocks;
use Mantle\Blocks\Block_Service_Provider;
use Mantle\Support\Environment;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Tests\Framework\Blocks\Fixtures\Blocks\Mock_Block;
use Mantle\Tests\Framework\Blocks\Fixtures\Dummy\Example_Block;
use Mockery as m;

class Test_Block_Provider extends Framework_Test_Case {
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

	/**
	 * Verify that the Discover_Blocks::within method returns the expected
	 * array of block class names.
	 */
	public function testBlocksAreFound() {
		$this->assertEquals( 'Mantle\\Tests', $this->app->get_namespace() );

		$expected = [
			Example_Block::class,
		];

		$found_blocks = Discover_Blocks::within(
			__DIR__ . '/fixtures/dummy',
			getcwd(),
		);

		$this->assertSame( $expected, $found_blocks );
	}

	/**
	 * Verify that the Block Service Provider locates, and registers, blocks
	 * as expected.
	 */
	public function testBlockProviderRegistersBlocks() {
		$expected = 1;

		/**
		 * First we need to configure the test application so it will find the blocks
		 * inside of our fixtures folder, and load them as if this were the Mantle app.
		 */
		$app = m::mock( Application::class )->makePartial();
		$app->set_base_path( getcwd() );
		$app->set_app_path( __DIR__ . '/fixtures' );

		$this->assertEquals( 'Mantle\\Tests', $app->get_namespace() );

		/**
		 * Now we need to boot our test application.
		 */
		$app->register( Block_Service_Provider::class );
		$app->boot();

		$this->assertSame( $expected, Mock_Block::$registrations );
	}
}
