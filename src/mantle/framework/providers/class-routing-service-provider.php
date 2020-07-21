<?php
/**
 * Routing_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Contracts\Http\Routing\Response_Factory as Response_Factory_Contract;
use Mantle\Framework\Http\Routing\Redirector;
use Mantle\Framework\Http\Routing\Response_Factory;
use Mantle\Framework\Http\Routing\Router;
use Mantle\Framework\Http\Routing\Url_Generator;
use Mantle\Framework\Service_Provider;

use function SML\remove_action_validated;

/**
 * Routing Service Provider
 *
 * Registers the application's core router and all dependencies of it.
 */
class Routing_Service_Provider extends Service_Provider {
	/**
	 * Callbacks to fire to dispatch Query Monitor.
	 *
	 * @var array
	 */
	protected $query_monitor_dispatches = [];

	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_router();
		$this->register_url_generator();
		$this->register_redirector();
		$this->register_response_factory();

		\add_filter( 'qm/dispatchers', [ $this, 'fix_query_monitor_dispatcher' ], PHP_INT_MAX );
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
	protected function register_url_generator() {
		$this->app->singleton(
			'url',
			function( $app ) {
				$routes = $app['router']->get_routes();
				$routes = $app->instance( 'routes', $routes );

				return new Url_Generator(
					$app->get_root_url(),
					$routes,
					$app['request']
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

	/**
	 * Fix the Query Monitor Dispatcher to properly fire in Mantle.
	 *
	 * @param \QM_Dispatcher[] $dispatchers Array of dispatchers.
	 * @return \QM_Dispatcher[]
	 */
	public function fix_query_monitor_dispatcher( $dispatchers ) {
		foreach ( [ 'ajax', 'html', 'wp_die' ] as $dispatcher ) {
			if ( isset( $dispatchers[ $dispatcher ] ) ) {
				$this->query_monitor_dispatches[] = [ $dispatchers[ $dispatcher ], 'dispatch' ];
			}
		}

		return $dispatchers;
	}

	/**
	 * Fire the Query Monitor dispatches and return the response.
	 *
	 * @return string|null
	 */
	public function fire_query_monitor_dispatches(): ?string {
		if ( empty( $this->query_monitor_dispatches ) ) {
			return null;
		}

		ob_start();

		foreach ( $this->query_monitor_dispatches as $callback ) {
			// Remove the dispatcher from the 'shutdown' hook.
			remove_action_validated( 'shutdown', $callback, 0 );
			$callback();
		}

		return (string) ob_get_clean();
	}
}
