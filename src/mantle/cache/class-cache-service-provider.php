<?php
/**
 * Cache_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Cache;

use Mantle\Support\Service_Provider;

/**
 * Cache Service Provider
 */
class Cache_Service_Provider extends Service_Provider {
	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->app->singleton( 'cache', fn ( $app ) => new WordPress_Cache_Repository( $app ) );
	}
}
