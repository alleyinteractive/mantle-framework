<?php
/**
 * Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework;

use Mantle\Framework\Console\Command;

/**
 * Application Service Provider
 */
abstract class Service_Provider {
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Commands to register.
	 * Register commands through `Service_Provider::add_command()`.
	 *
	 * @var \Mantle\Framework\Console\Command[]
	 */
	protected $commands;

	/**
	 * Create a new service provider instance.
	 *
	 * @param Application $app Application Instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Register any application services.
	 */
	public function register() { }

	/**
	 * Bootstrap services.
	 */
	public function boot() { }

	/**
	 * Register a wp-cli command.
	 *
	 * @param Command|string $command Command instance or class name to register.
	 */
	public function add_command( $command ) {
		if ( $command instanceof Command ) {
			$this->commands[] = $command;
		} else {
			$this->commands[] = $this->app->make( $command );
		}
	}

	/**
	 * Register the wp-cli commands for a service provider.
	 */
	public function register_commands() {
		foreach ( $this->commands as $command ) {
			$command->register();
		}
	}
}
