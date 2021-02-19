<?php
/**
 * Routing_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Contracts\Http\Routing\Response_Factory as Response_Factory_Contract;
use Mantle\Http\Routing\Redirector;
use Mantle\Http\Routing\Response_Factory;
use Mantle\Http\Routing\Router;
use Mantle\Http\Routing\Url_Generator;
use Mantle\Support\Service_Provider;

/**
 * Routing Service Provider
 *
 * Registers the application's core router and all dependencies of it. Supports
 * the application-defined `Route_Service_Provider` in providing the core
 * services of routing.
 */
class Routing_Service_Provider extends Service_Provider {
	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_router();
		$this->register_url_generator();
		$this->register_redirector();
		$this->register_response_factory();
	}

	/**
	 * Register the routing service provider instance.
	 */
	protected function register_router() {
		$this->app->singleton(
			'router',
			function( $app ) {
				return new Router( $app['events'], $app );
			}
		);
	}

	/**
	 * Register the URL generator service.
	 *
	 * @todo Improve on this URL generator to allow the routes to be shared
	 * instantly from the router.
	 */
	protected function register_url_generator() {
		$this->app->singleton(
			'url',
			function( $app ) {
				$routes = $app['router']->get_routes();
				$routes = $app->instance( 'routes', $routes );

				return new Url_Generator(
					$app->get_root_url(),
					$routes,
					$app['request'],
					$app['log'],
				);
			}
		);
	}

	/**
	 * Register the redirect service.
	 */
	protected function register_redirector() {
		$this->app->singleton(
			'redirect',
			function( $app ) {
				return new Redirector( $app['url'] );
			}
		);
	}

	/**
	 * Register the response factory.
	 */
	protected function register_response_factory() {
		$this->app->singleton(
			Response_Factory_Contract::class,
			function( $app ) {
				return new Response_Factory( $app['redirect'], $app['view'] );
			}
		);
	}
}
