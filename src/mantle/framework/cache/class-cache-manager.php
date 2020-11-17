<?php
/**
 * Cache_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Cache;

use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Contracts\Cache\Factory;
use Mantle\Framework\Contracts\Cache\Repository;
use Mantle\Framework\Support\Driver_Manager;

/**
 * Cache Manager
 */
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
	 * Retrieve the default store name.
	 *
	 * @return string
	 */
	protected function get_default_store(): string {
		return (string) $this->app['config']['cache.default'];
	}

	/**
	 * Retrieve a store's config.
	 *
	 * @param string $name Store name.
	 * @return array
	 */
	protected function get_config( string $name ): array {
		return (array) $this->app['config'][ "cache.stores.{$name}" ];
	}

	/**
	 * Create a WordPress adapter.
	 *
	 * @param array $config Configuration.
	 * @return Repository
	 */
	protected function create_wordpress_driver( array $config ): Repository {
		return new WordPress_Repository( $config['prefix'] ?? '' );
	}

	/**
	 * Create a array adapter.
	 *
	 * @param array $config Configuration.
	 * @return Repository
	 */
	protected function create_array_driver( array $config ): Repository {
		return new Array_Repository( $config['prefix'] ?? '' );
	}

	/**
	 * Create a Redis adapter.
	 *
	 * @param array $config Configuration.
	 * @return Repository
	 */
	protected function create_redis_driver( array $config ): Repository {
		return new Redis_Repository( $config );
	}
}
