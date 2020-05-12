<?php

namespace Mantle\Tests\Support\Providers;

use Mantle\Framework\Application;
use Mantle\Framework\Config\Repository;
use Mantle\Framework\Support\Providers\Model_Register_Provider;
use Mantle\Framework\Contracts\Database\Registrable as Registrable_Contract;
use Mantle\Framework\Support\Providers\Provider_Exception;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class Test_Model_Register_Provider extends TestCase {
	public function tearDown() {
		parent::tearDown();
		m::close();
	}

	public function test_register_model() {
		$app    = new Application();
		$config = new Repository();

		$mock = m::mock( Registrable_Contract::class );
		$mock->shouldReceive( 'register' )->once();

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
