<?php
namespace Mantle\Tests\Support;

use Mantle\Framework\Application;
use Mantle\Console\Command;
use Mantle\Contracts\Providers as ProviderContracts;
use Mantle\Support\Service_Provider;
use Mantle\Support\Attributes\Action;
use Mockery as m;

class Test_Service_Provider extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	protected function setUp(): void {
		parent::setUp();

		remove_all_actions( 'init' );
		remove_all_filters( 'custom_filter' );
	}

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

	public function test_hook_method_action() {
		$_SERVER['__hook_fired'] = false;

		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		do_action( 'custom_hook' );

		$this->assertTrue( $_SERVER['__hook_fired'] );
	}

	public function test_hook_method_filter() {
		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$value = apply_filters( 'custom_filter', 5 );

		$this->assertEquals( 15, $value );
	}

	public function test_hook_attribute() {
		// Abandon if we're not running PHP 8.
		if ( version_compare( phpversion(), '8.0.0', '<' ) ) {
			$this->markTestSkipped( 'Requires PHP 8.0.0 or greater.' );
			return;
		}

		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		do_action( 'testable-attribute-hook' );

		$this->assertTrue( $_SERVER['__custom_hook_fired'] ?? false );
	}
}

class Provider_Test_Hook extends Service_Provider {
	public function on_custom_hook() {
		$_SERVER['__hook_fired'] = true;
	}

	public function on_custom_filter( $value ) {
		return $value + 10;
	}

	#[Action('testable-attribute-hook', 20)]
	public function handle_custom_hook() {
		$_SERVER['__custom_hook_fired'] = true;
	}

	public function handle_custom_filter( $value ) {
		return $value + 100;
	}
}
