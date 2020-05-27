<?php
/**
 * Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Http\Routing\Router as Router_Contract;
// use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Router
 *
 * Allow registration of routes to the application.
 */
class Router implements Router_Contract {
	/**
	 * Route Collection
	 *
	 * @var RouteCollection
	 */
	protected $routes;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->routes = new RouteCollection();
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function get( string $uri, $action ) {
		return $this->add_route( [ 'GET', 'HEAD' ], $uri, $action );
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function post( string $uri, $action ) {
		return $this->add_route( [ 'POST' ], $uri, $action );
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function put( string $uri, $action ) {
		return $this->add_route( [ 'PUT' ], $uri, $action );
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function delete( string $uri, $action ) {
		return $this->add_route( [ 'DELETE' ], $uri, $action );
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function patch( string $uri, $action ) {
		return $this->add_route( [ 'PATCH' ], $uri, $action );
	}

	/**
	 * Register a OPTIONS route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function options( string $uri, $action ) {
		return $this->add_route( [ 'OPTIONS' ], $uri, $action );
	}

	/**
	 * Register a route.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 * @return Route
	 */
	public function add_route( array $methods, string $uri, $action ) {
		$route = new Route( $methods, $uri, $action );

		$this->routes->add( $route->get_route_name(), $route );
		return $route;
	}

	/**
	 * Convert the route callback to a consistent format the kernel understands.
	 *
	 * @param mixed $action Route callback.
	 * @return array
	 */
	// protected function handle_route_action( $action ): array {
	// 	if ( is_callable( $action ) ) {
	// 		return [
	// 			'callback' => $action,
	// 		];
	// 	}

	// 	if ( is_string( $action ) ) {
	// 		return [
	// 			'callback' => $action,
	// 		];
	// 	}

	// 	return (array) $action;
	// }

	/**
	 * Get registered routes.
	 *
	 * @return RouteCollection
	 */
	public function get_routes(): RouteCollection {
		return $this->routes;
	}
}
