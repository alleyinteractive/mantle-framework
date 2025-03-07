<?php

namespace Mantle\Tests\Providers;

use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Database\Model_Service_Provider;
use Mantle\Contracts\Database\Registrable as Registrable_Contract;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Registration\Register_Post_Type;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ModelRegisterProviderTest extends MockeryTestCase {
	protected function setUp(): void {
		parent::setUp();
		remove_all_actions( 'init' );
	}

	public function test_register_model() {
		$app    = new Application();
		$config = new Repository();

		$mock = m::mock( Model::class, Registrable_Contract::class, Register_Post_Type::class )
			->makePartial();
		$mock->shouldReceive( 'register_object' )->once();

		$app->instance( 'config', $config );
		$config->set(
			'models',
			[
				'register' => [
					get_class( $mock ),
				],
			]
		);


		$provider = new Model_Service_Provider( $app );
		$provider->register();
		$provider->boot();

		// Call 'init' to allow the register_object method to be called.
		do_action( 'init' );
	}
}
