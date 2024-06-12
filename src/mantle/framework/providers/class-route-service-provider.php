<?php
/**
 * Route_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Support\Service_Provider;

/**
 * Route Service Provider
 *
 * @deprecated No longer needed in Mantle >= 1.1 but kept around for backwards compatibility. Will be removed in in Mantle 2.0.
 */
class Route_Service_Provider extends Service_Provider implements Isolated_Service_Provider {
	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void {
		parent::boot();

		$this->app->booted( fn () => $this->load_routes() );
	}

	/**
	 * Load routes from the application service provider.
	 */
	protected function load_routes() {
		if ( method_exists( $this, 'map' ) ) {
			$this->app->call( [ $this, 'map' ] );
		}

		// Sync the loaded routes to the URL generator.
		$this->app['router']->sync_routes_to_url_generator();
	}

	/**
	 * Set a callback to determine if a request should be passed down to WordPress.
	 * Pass through to the new router method.
	 *
	 * @param callable $callback Callback to invoke.
	 */
	protected function set_pass_through_callback( callable $callback ) {
		$this->app['router']->pass_requests_to_wordpress( $callback );
	}

	/**
	 * Allow pass through requests to WordPress.
	 *
	 * @return static
	 */
	protected function allow_pass_through_requests() {
		$this->app['router']->pass_requests_to_wordpress( true );

		return $this;
	}

	/**
	 * Prevent pass through requests to WordPress.
	 *
	 * @return static
	 */
	protected function prevent_pass_through_requests() {
		$this->app['router']->pass_requests_to_wordpress( false );

		return $this;
	}
}
