<?php
/**
 * Filesystem_Service_Provider class file.
 *
 * @package mantle
 */

namespace Mantle\Framework\Filesystem;

use Mantle\Framework\Service_Provider;

/**
 * Filesystem Service Provider
 */
class Filesystem_Service_Provider extends Service_Provider {

	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_flysystem();
	}

	/**
	 * Register the Flysystem Manager
	 */
	public function register_flysystem() {
		$this->app->singleton(
			'filesystem',
			function ( $app ) {
				return new Filesystem_Manager( $app );
			}
		)
	}
}
