<?php
/**
 * Route_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Http\Routing\Router;
use Mantle\Framework\Service_Provider;
use Mantle\Framework\Support\Forward_Calls;

/**
 * Route Service Provider
 */
class Route_Service_Provider extends Service_Provider {
	use Forward_Calls;

	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_router();
	}

	/**
	 * Register the router singleton instance.
	 */
	protected function register_router() {
		$this->app->singleton(
			'router',
			function( $app ) {
				return new Router( $app );
			}
		);
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot() {
		parent::boot();
		$this->load_routes();
	}

	/**
	 * Load routes from the application service provider.
	 */
	protected function load_routes() {
		if ( method_exists( $this, 'map' ) ) {
			$this->app->call( [ $this, 'map' ] );
		}
	}

	/**
	 * Pass dynamic methods onto the router instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call( string $method, array $parameters ) {
		// return $this->forward_call_to(
		// 	$this->app->make( Router::class ), $method, $parameters
		// );
	}
}
