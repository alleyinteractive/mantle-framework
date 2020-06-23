<?php
/**
 * Test_View_Factory test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Http;

use Mantle\Framework\Application;
use Mantle\Framework\Facade\Facade;
use Mantle\Framework\Facade\View;
use Mantle\Framework\Http\View\Factory;
use Mockery as m;

class Test_View_Factory extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * @var Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->app = new Application();
		$this->factory = new Factory( $this->app );
		$this->app->instance( 'view', $this->factory );

		Facade::clear_resolved_instances();
		Facade::set_facade_application( $this->app );
	}

	public function test_share_service_provider() {

		$this->factory = $this->app['view'];

		$this->assertEquals( 'default-value', $this->factory->shared( 'test-to-share', 'default-value' ) );

		// Share data as if it was from a service provider.
		View::share( 'test-to-share', 'the-value-to-compare' );

		$this->assertEquals( 'the-value-to-compare', $this->factory->shared( 'test-to-share', 'default-value' ) );
		$this->assertEquals( 'the-value-to-compare', $this->factory->get_shared()['test-to-share'] ?? '' );

		// Ensure you can get nested data.
		View::share(
			'nested-data',
			[
				'level0' => [
					'level1' => [
						'level2' => 'nested-value',
					],
				],
			]
		);

		$this->assertEquals( 'nested-value', $this->factory->shared( 'nested-data.level0.level1.level2' ) );
	}
}
