<?php
/**
 * Route_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Contracts\Providers\Route_Service_Provider as Route_Service_Provider_Contract;
use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Http\Request;
use Mantle\Support\Service_Provider;

/**
 * Route Service Provider
 */
class Route_Service_Provider extends Service_Provider implements Route_Service_Provider_Contract, Isolated_Service_Provider {
	/**
	 * Allow requests to be passed down to WordPress.
	 *
	 * @var bool|callable
	 */
	protected $pass_requests_to_wp = true;

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->register_router_service_provider();
	}

	/**
	 * Register the router service provider.
	 */
	protected function register_router_service_provider() {
		$this->app->instance( 'router.service-provider', $this );
	}

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

		// Setup the default request object.
		if ( ! isset( $this->app['request'] ) ) {
			$this->app['request'] = new Request();
		}

		// Sync the loaded routes to the URL generator.
		$this->app['router']->sync_routes_to_url_generator();
	}

	/**
	 * Determine if requests should pass through to WordPress.
	 *
	 * @param Request $request Request instance.
	 */
	public function should_pass_through_requests( Request $request ): bool {
		if ( str_starts_with( $request->path(), 'wp-json' ) ) {
			return true;
		}

		if ( ! wp_using_themes() ) {
			return true;
		}

		if ( is_callable( $this->pass_requests_to_wp ) ) {
			return (bool) $this->app->call( $this->pass_requests_to_wp );
		}

		return (bool) $this->pass_requests_to_wp;
	}

	/**
	 * Set a callback to determine if a request should be passed down to WordPress.
	 *
	 * @param callable $callback Callback to invoke.
	 */
	protected function set_pass_through_callback( callable $callback ) {
		$this->pass_requests_to_wp = $callback;
	}

	/**
	 * Allow pass through requests to WordPress.
	 *
	 * @return static
	 */
	protected function allow_pass_through_requests() {
		$this->pass_requests_to_wp = true;
		return $this;
	}

	/**
	 * Prevent pass through requests to WordPress.
	 *
	 * @return static
	 */
	protected function prevent_pass_through_requests() {
		$this->pass_requests_to_wp = false;
		return $this;
	}
}
