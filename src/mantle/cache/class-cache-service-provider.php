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
	public function register() {
		$this->app->singleton(
			'cache',
			function( $app ) {
				return new Cache_Manager( $app );
			}
		);
	}
}
