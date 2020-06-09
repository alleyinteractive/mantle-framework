<?php
/**
 * Routing_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Contracts\Http\Routing\Response_Factory as Response_Factory_Contract;
use Mantle\Framework\Http\Routing\Response_Factory;
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
				return new Router( $app );
			}
		);
	}

	/**
	 * Register the URL generator service.
	 */
	protected function register_url_generator_service() {

	}

	/**
	 * Register the redirect service.
	 */
	protected function register_redirector() {

	}

	/**
	 * Register the response factory.
	 */
	protected function register_response_factory() {
		$this->app->singleton(
			Response_Factory_Contract::class,
			function( $app ) {
				return new Response_Factory();
			}
		);
	}
}
