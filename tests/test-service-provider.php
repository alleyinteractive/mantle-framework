<?php
namespace Mantle\Tests;

use Mantle\Framework\Application;
use Mantle\Framework\Console\Command;
use Mantle\Framework\Service_Provider;
use Mockery as m;

class Test_Service_Provider extends \Mockery\Adapter\Phpunit\MockeryTestCase {

	public function test_service_provider_registered() {
		$service_provider = m::mock( Service_Provider::class )->makePartial();
		$service_provider->shouldReceive( 'register' )->once();
		$service_provider->shouldNotReceive( 'boot' );

		$app = m::mock( Application::class )->makePartial();
		$app->register( $service_provider );

		$this->assertFalse( $app->is_booted() );
	}

	public function test_service_provider_booted() {
		$service_provider = m::mock( Service_Provider::class )->makePartial();
		$service_provider->shouldReceive( 'register' )->once();
		$service_provider->shouldReceive( 'boot' )->once();

		$app = m::mock( Application::class )->makePartial();
		$app->register( $service_provider );

		$this->assertFalse( $app->is_booted() );
		$app->boot();
		$this->assertTrue( $app->is_booted() );
	}

	public function test_register_commands() {
		$command = m::mock( Command::class )->makePartial();
		$command->shouldReceive( 'register' )->once();

		$service_provider = m::mock( Service_Provider::class )->makePartial();
		$service_provider
			->add_command( $command )
			->register_commands();
	}
}
