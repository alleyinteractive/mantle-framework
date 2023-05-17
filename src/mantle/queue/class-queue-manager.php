<?php
/**
 * Queue_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use InvalidArgumentException;
use Mantle\Contracts\Container;
use Mantle\Contracts\Queue\Provider;
use Mantle\Contracts\Queue\Queue_Manager as Queue_Manager_Contract;

/**
 * Queue Manager
 */
class Queue_Manager implements Queue_Manager_Contract {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	protected Container $container;

	/**
	 * Provider class map.
	 *
	 * @var class-string<Provider>[]|Provider[]
	 */
	protected array $providers = [];

	/**
	 * Provider connections.
	 *
	 * @var Provider[]
	 */
	protected $connections = [];

	/***
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
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
	 * @param string                          $name Provider name.
	 * @param Provider|class-string<Provider> $provider Provider class name/instance.
	 * @return static
	 *
	 * @throws InvalidArgumentException Thrown invalid provider.
	 */
	public function add_provider( string $name, $provider ) {
		if ( is_string( $provider ) && ( ! class_exists( $provider ) || ! in_array( Provider::class, class_implements( $provider ), true ) ) ) {
			throw new InvalidArgumentException( "Provider does not implement Provider contract: [$provider]" );
		} elseif ( is_object( $provider ) && ! ( $provider instanceof Provider ) ) { // @phpstan-ignore-line is always false
			throw new InvalidArgumentException( "Provider does not implement Provider contract: [$provider::class]" );
		}

		$this->providers[ $name ] = $provider;

		return $this;
	}

	/**
	 * Get the default queue driver in queue.
	 *
	 * @return string|null
	 */
	protected function get_default_driver(): ?string {
		if ( ! isset( $this->container['config'] ) ) {
			return null;
		}

		return $this->container['config']['queue.default'] ?? 'wordpress';
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

		if ( ! is_object( $this->providers[ $provider ] ) ) {
			$this->connections[ $provider ] = $this->container->make( $this->providers[ $provider ] );
		} else {
			$this->connections[ $provider ] = $this->providers[ $provider ];
		}

		if ( ! ( $this->connections[ $provider ] instanceof Provider ) ) {
			throw new InvalidArgumentException( "Unknown provider instance resolved for [$provider]: " . get_class( $this->connections[ $provider ] ) );
		}

		return $this->connections[ $provider ];
	}
}
