<?php
namespace Mantle\Tests\Framework\Log;

use Mantle\Framework\Application;
use Mantle\Config\Repository;
use Mantle\Facade\Facade;
use Mantle\Facade\Log;
use Mantle\Log\Log_Manager;
use Monolog\Handler\TestHandler;

class Test_Log_Manager extends \Mockery\Adapter\Phpunit\MockeryTestCase {

	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * @var Log_Manager
	 */
	protected $instance;

	/**
	 * @var TestHandler
	 */
	protected $handler;

	protected function setUp(): void {
		parent::setUp();

		$this->app     = new Application();
		$this->instance = new Log_Manager( $this->app );
		$this->handler = new TestHandler( 'info' );

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
						'another_channel' => [
							'driver'  => 'custom',
							'handler' => new TestHandler(),
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

	protected function tearDown(): void {
		parent::tearDown();
		Facade::clear_resolved_instances();
	}

	public function test_normal_logging() {
		$this->instance->info( 'Test Message' );
		$this->instance->error( 'Error Message' );
		$this->instance->debug( 'Debug Message to ignore' );

		// Check if the handler received the message.
		$this->assertTrue( $this->handler->hasRecord( 'Test Message', 'info' ) );
		$this->assertTrue( $this->handler->hasRecord( 'Error Message', 'error' ) );

		// Logger should have ignored the debug message.
		$this->assertFalse( $this->handler->hasRecord( 'Debug Message to ignore', 'debug' ) );
	}

	public function test_channel_method() {
		$this->instance->channel( [ 'testing' ] )->info( 'Channel method' );
		$this->assertTrue( $this->handler->hasRecord( 'Channel method', 'info' ) );

		$this->instance->channel( [ 'another_channel' ] )->info( 'Another channel message' );
		$this->assertFalse( $this->handler->hasRecord( 'Another channel message', 'info' ) );
	}

	public function test_stack_handler() {
		$this->instance->channel( 'stack' )->info( 'Stack logging' );
		$this->assertTrue( $this->handler->hasRecord( 'Stack logging', 'info' ) );
	}

	public function test_logging_with_facade() {
		Log::info( 'Facade info message' );
		Log::error( 'Facade error message' );

		$this->assertTrue( $this->handler->hasRecord( 'Facade info message', 'info' ) );
		$this->assertTrue( $this->handler->hasRecord( 'Facade error message', 'error' ) );
	}
}
