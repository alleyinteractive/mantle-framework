<?php
/**
 * Routing_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Http\Routing\Router;
use Mantle\Framework\Service_Provider;

/**
 * Routing Service Provider
 *
 * Registers the application's core router and all dependencies of it.
 */
class Routing_Service_Provider extends Service_Provider {
	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_router();
	}

	/**
	 * Register the routing service provider instance.
	 */
	protected function register_router() {
		$this->app->singleton(
			'router',
			function( $app ) {
				return new Router( $app );
			}
		);
	}
}
