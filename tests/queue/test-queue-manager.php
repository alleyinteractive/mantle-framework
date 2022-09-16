<?php
namespace Mantle\Tests\Queue;

use Mantle\Framework\Application;
use Mantle\Config\Repository;
use Mantle\Contracts\Queue\Provider;
use Mantle\Queue\Queue_Manager;
use Mockery as m;

/**
 * @group queue
 */
class Test_Queue_Manager extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	public function test_default_connection() {
		$provider = m::mock( Provider::class );

		$config = new Repository(
			[
				'queue' => [
					'default' => 'test',
				],
			]
		);

		$app = new Application();
		$app->instance( 'config', $config );

		$manager = new Queue_Manager( $app );
		$manager->add_provider( 'test', get_class( $provider ) );

		$this->assertInstanceOf( get_class( $provider ), $manager->get_provider() );
	}

	public function test_another_connection() {
		$provider   = m::mock( Provider::class );
		$provider_b = m::mock( Provider::class );

		$config = new Repository(
			[
				'queue' => [
					'default' => 'test',
				],
			]
		);

		$app = new Application();
		$app->instance( 'config', $config );

		$manager = new Queue_Manager( $app );
		$manager->add_provider( 'test', get_class( $provider ) );
		$manager->add_provider( 'test_another', get_class( $provider_b ) );

		$this->assertInstanceOf( get_class( $provider_b ), $manager->get_provider( 'test_another' ) );
	}
}
