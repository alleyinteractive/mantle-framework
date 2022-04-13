<?php

namespace Mantle\Tests\Helpers;

use Mantle\Config\Repository;
use Mantle\Container\Container;
use Mantle\Facade\Facade;
use Mantle\Framework\Application;
use Mantle\Log\Log_Manager;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

use function Mantle\Support\Helpers\info;
use function Mantle\Support\Helpers\is_hosted_env;
use function Mantle\Support\Helpers\is_local_env;
use function Mantle\Support\Helpers\logger;

class Test_Helpers extends TestCase {
	protected Application $app;
	protected Log_Manager $instance;
	protected TestHandler $handler;

	protected function setUp(): void {
		parent::setUp();

		$this->app      = new Application();
		$this->instance = new Log_Manager( $this->app );
		$this->handler  = new TestHandler( 'debug' );

		Application::set_instance( $this->app );
		Container::set_instance( $this->app );

		// Setup the config for testing.
		$config = new Repository(
			[
				'logging' => [
					'default'  => 'testing',
					'channels' => [
						'testing' => [
							'driver'  => 'custom',
							'handler' => $this->handler,
						],
						'stack' => [
							'driver'   => 'stack',
							'channels' => [ 'testing' ],
						]
					],
				],
			]
		);

		$this->app->instance( 'config', $config );
		$this->app->instance( 'log', $this->instance );

		Facade::set_facade_application( $this->app );
	}

	public function test_is_hosted_env() {
		$this->app->set_environment( 'production' );

		$this->assertTrue( is_hosted_env() );
		$this->assertFalse( is_local_env() );

		$this->app->set_environment( 'local' );

		$this->assertFalse( is_hosted_env() );
		$this->assertTrue( is_local_env() );
	}

	public function test_info_helpers() {
		info( 'This is a helper test message.' );
		$this->assertTrue( $this->handler->hasRecord( 'This is a helper test message.', 'info' ) );
	}

	public function test_logger_helpers() {
		logger( 'This is a helper debug message.' );
		logger()->warning( 'This is a warning.' );

		$this->assertTrue( $this->handler->hasRecord( 'This is a helper debug message.', 'debug' ) );
		$this->assertTrue( $this->handler->hasRecord( 'This is a warning.', 'warning' ) );
	}
}
