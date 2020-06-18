<?php
namespace Mantle\Tests;

use Mantle\Framework\Application;
use Mantle\Framework\Console\Command;
use Mantle\Framework\Contracts\Providers as ProviderContracts;
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

	public function test_on_init() {
		$provider = m::mock( Service_Provider::class, ProviderContracts\Init::class )->makePartial();
		$provider->shouldReceive( 'on_init' )->once();

		$provider->boot();
		do_action( 'init' );
	}

	public function test_on_wp_loaded() {
		$provider = m::mock( Service_Provider::class, ProviderContracts\Wp_Loaded::class )->makePartial();
		$provider->shouldReceive( 'on_wp_loaded' )->once();

		$provider->boot();
		do_action( 'wp_loaded' );
	}
}
