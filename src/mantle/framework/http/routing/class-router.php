<?php
/**
 * Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Http\Routing\Router as Router_Contract;
use Symfony\Component\Routing\Route;
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

	public function get( string $uri, $action ) {
		return $this->add_route( [ 'GET', 'HEAD' ], $uri, $action );
	}

	public function post( string $uri, $action ) {
		return $this->add_route( [ 'POST' ], $uri, $action );
	}

	public function put( string $uri, $action ) {
		return $this->add_route( [ 'PUT' ], $uri, $action );
	}

	public function delete( string $uri, $action ) {
		return $this->add_route( [ 'DELETE' ], $uri, $action );
	}

	public function patch( string $uri, $action ) {
		return $this->add_route( [ 'PATCH' ], $uri, $action );
	}

	public function options( string $uri, $action ) {
		return $this->add_route( [ 'OPTIONS' ], $uri, $action );
	}

	/**
	 * Register a route.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 * @return static
	 */
	public function add_route( array $methods, string $uri, $action ) {
		$route = new Route( $uri, $this->handle_route_action( $action ) );
		$name  = $this->generate_route_name( $methods, $uri, $action );

		$this->routes->add( $name, $route );
		return $this;
	}

	/**
	 * Generate the route name.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 * @return string
	 */
	protected function generate_route_name( $methods, $uri, $action ): string {
		if ( is_array( $action ) && ! empty( $action['name'] ) ) {
			return $action['name'];
		}

		return implode( '.', $methods ) . ":{$uri}";
	}

	/**
	 * Convert the route callback to a consistent format the kernel understands.
	 *
	 * @param mixed $action Route callback.
	 * @return array
	 */
	protected function handle_route_action( $action ): array {
		if ( is_callable( $action ) ) {
			return [
				'action' => $action,
			];
		}

		if ( is_string( $action ) ) {
			return [
				'action' => $action,
			];
		}

		return (array) $action;
	}

	/**
	 * Get registered routes.
	 *
	 * @return RouteCollection
	 */
	public function get_routes(): RouteCollection {
		return $this->routes;
	}
}
