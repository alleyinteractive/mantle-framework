<?php
/**
 * Queue_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue;

use InvalidArgumentException;
use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Contracts\Queue\Provider;
use Mantle\Framework\Contracts\Queue\Queue_Manager as Queue_Manager_Contract;

/**
 * Queue Manager
 */
class Queue_Manager implements Queue_Manager_Contract {
	/**
	 * Constructor.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Provider class map.
	 *
	 * @var string[]
	 */
	protected $providers = [];

	/**
	 * Provider connections.
	 *
	 * @var Provider[]
	 */
	protected $connections = [];

	/***
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Get a queue provider instance.
	 *
	 * @param string $name Provider name, optional.
	 * @return Provider
	 */
	public function get_provider( string $name = null ): Provider {
		$name = $name ?: $this->get_default_driver();

		if ( ! isset( $this->connections[ $name ] ) ) {
			$this->connections[ $name ] = $this->resolve( $name );
		}

		return $this->connections[ $name ];
	}

	/**
	 * Add a provider for the queue manager.
	 *
	 * @param string $name Provider name.
	 * @param string $provider_class Provider class name.
	 * @return static
	 *
	 * @throws InvalidArgumentException Thrown invalid provider.
	 */
	public function add_provider( string $name, string $provider_class ) {
		if ( ! class_implements( $provider_class, Provider::class ) ) {
			throw new InvalidArgumentException( "Provider does not implement Provider contract: [$provider_class]" );
		}

		$this->providers[ $name ] = $provider_class;
		return $this;
	}

	/**
	 * Get the default queue driver in queue.
	 *
	 * @return string|null
	 */
	protected function get_default_driver(): ?string {
		return $this->app['config']['queue.default'] ?? null;
	}

	/**
	 * Resolve a connection to a queue provider.
	 *
	 * @param string $provider Provider name.
	 * @return Provider
	 *
	 * @throws InvalidArgumentException Thrown on invalid provider name.
	 * @throws InvalidArgumentException Thrown on invalid provider instance resolved.
	 */
	protected function resolve( string $provider ): Provider {
		if ( ! isset( $this->providers[ $provider ] ) ) {
			throw new InvalidArgumentException( "No provider found for [$provider]." );
		}

		$this->connections[ $provider ] = $this->app->make( $this->providers[ $provider ] );

		if ( ! ( $this->connections[ $provider ] instanceof Provider ) ) {
			throw new InvalidArgumentException( "Unknown provider instance resolved for [$provider]: " . get_class( $this->connections[ $provider ] ) );
		}

		return $this->connections[ $provider ];
	}
}
