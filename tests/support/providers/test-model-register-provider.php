<?php

namespace Mantle\Tests\Providers;

use Mantle\Framework\Application;
use Mantle\Framework\Config\Repository;
use Mantle\Framework\Providers\Model_Register_Provider;
use Mantle\Framework\Contracts\Database\Registrable as Registrable_Contract;
use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
use Mantle\Framework\Providers\Provider_Exception;
use PHPUnit\Framework\TestCase;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class Test_Model_Register_Provider extends MockeryTestCase {
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


		$provider = new Model_Register_Provider( $app );
		$provider->register();
		$provider->boot();

		// Call 'init' to allow the register_object method to be called.
		do_action( 'init' );
	}

	public function test_register_invalid_model() {
		$app    = new Application();
		$config = new Repository();

		$this->expectException( Provider_Exception::class );
		$this->expectExceptionMessage(
			Invalid_Model_Example::class . ' does not implement ' . Registrable_Contract::class . ' interface'
		);

		$app->instance( 'config', $config );
		$config->set(
			'models',
			[
				'register' => [
					Invalid_Model_Example::class,
				],
			]
		);

		$provider = new Model_Register_Provider( $app );
		$provider->register();
		$provider->boot();
	}
}

class Invalid_Model_Example { }
