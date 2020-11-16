<?php
namespace Mantle\Framework\Cache;

use InvalidArgumentException;
use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Contracts\Cache\Factory;
use Mantle\Framework\Contracts\Cache\Repository;
use Mantle\Framework\Support\Arr;
use Mantle\Framework\Support\Driver_Manager;

class Cache_Manager extends Driver_Manager implements Factory {
	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Resolved cache stores.
	 *
	 * @var Repository[]
	 */
	protected $resolved;

	/**
	 * Create a new Cache manager instance.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Retrieve a cache store by name.
	 *
	 * @param string|null $name Cache store name.
	 * @return Repository
	 */
	public function store( string $name = null ): Repository {
		$name = $name ?: $this->get_default_driver();

		if ( ! isset( $this->resolved[ $name ] ) ) {
			$this->resolved[ $name ] = $this->resolve( $name );
		}

		return $this->resolved[ $name ];
	}

	/**
	 * Retrieve the default driver name.
	 *
	 * @return string
	 */
	protected function get_default_driver(): string {
		return (string) $this->app['config']['cache.default'];
	}

	/**
	 * Retrieve a store's config.
	 *
	 * @param string $name Store name.
	 * @return array
	 */
	protected function get_config( string $name ): array {
		return (array) $this->app['config']["stores.{$name}"];
	}

	/**
	 * Resolve an instance of a cache store.
	 *
	 * @param string $name Cache store.
	 * @return Repository
	 */
	protected function resolve( string $name ): Repository {
		$config = $this->get_config( $name );
		$driver = Arr::pull( $config, 'driver' );

		if ( empty( $driver ) ) {
			throw new InvalidArgumentException( "Driver not specified for [$name}." );
		}

		$instance = $this->resolve_driver( $driver, $config );
		dd($name, $config, $instance);
	}

	protected function create_wordpress_driver(): Repository {

	}
}
