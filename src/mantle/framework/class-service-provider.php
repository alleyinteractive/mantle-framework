<?php
/**
 * Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework;

use Mantle\Framework\Contracts\Providers as ProviderContracts;
use Mantle\Console\Command;
use Mantle\Support\Str;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait};

use function add_action;
use function Mantle\Framework\Helpers\class_uses_recursive;
use function Mantle\Framework\Helpers\collect;

/**
 * Application Service Provider
 */
abstract class Service_Provider implements LoggerAwareInterface {
	use LoggerAwareTrait;

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
	 * @var \Mantle\Console\Command[]
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
	 * Register the service provider.
	 */
	public function register() { }

	/**
	 * Bootstrap services.
	 */
	public function boot() {
		if ( isset( $this->app['log'] ) ) {
			$this->setLogger( $this->app['log']->default_logger() );
		}

		$this->boot_action_hooks();
		$this->boot_contracts();
	}

	/**
	 * Boot all actions on the service provider.
	 *
	 * Allow methods in the 'on_{hook}_at_priority' and 'on_{hook}' format
	 * to automatically register WordPress hooks.
	 */
	protected function boot_action_hooks() {
		collect( get_class_methods( static::class ) )
			->filter(
				function( string $method ) {
					return Str::starts_with( $method, 'on_' );
				}
			)
			->each(
				function( string $method ) {
					$hook     = Str::after( $method, 'on_' );
					$priority = 10;

					if ( Str::contains( $hook, '_at_' ) ) {
						// Strip the priority from the hook name.
						$priority = (int) Str::after_last( $hook, '_at_' );
						$hook     = Str::before_last( $hook, '_at_' );
					}

					\add_action( $hook, [ $this, $method ], $priority, 99 );
				}
			);
	}

	/**
	 * Boot the service provider's contracts.
	 *
	 * @deprecated Replaced with boot_action_hooks.
	 */
	protected function boot_contracts() {
		if ( $this instanceof ProviderContracts\Init ) {
			add_action( 'init', [ $this, 'on_init' ] );
		}

		if ( $this instanceof ProviderContracts\Wp_Loaded ) {
			add_action( 'wp_loaded', [ $this, 'on_wp_loaded' ] );
		}
	}

	/**
	 * Register a wp-cli command.
	 *
	 * @param Command[]|string[]|Command|string $command Command instance or class name to register.
	 * @return Service_Provider
	 */
	public function add_command( $command ): Service_Provider {
		if ( is_array( $command ) ) {
			foreach ( $command as $item ) {
				$this->add_command( $item );
			}
		} elseif ( $command instanceof Command ) {
			$this->commands[] = $command;
		} else {
			$this->commands[] = $this->app->make( $command );
		}

		return $this;
	}

	/**
	 * Register the wp-cli commands for a service provider.
	 *
	 * @return Service_Provider
	 */
	public function register_commands(): Service_Provider {
		foreach ( (array) $this->commands as $command ) {
			$command->register();
		}

		return $this;
	}
}
