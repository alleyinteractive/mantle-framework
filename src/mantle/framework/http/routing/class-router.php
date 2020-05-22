<?php
namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Http\Routing\Router as Router_Contract;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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

	public function get( string $uri, $action, string $name = '' ) {
		return $this->add_route( [ 'GET', 'HEAD' ], $uri, $action, $name );
	}

	public function post( string $uri, $action, string $name = '' ) {
		return $this->add_route( [ 'POST' ], $uri, $action, $name );
	}

	public function put( string $uri, $action, string $name = '' ) {
		return $this->add_route( [ 'PUT' ], $uri, $action, $name );
	}

	public function delete( string $uri, $action, string $name = '' ) {
		return $this->add_route( [ 'DELETE' ], $uri, $action, $name );
	}

	public function patch( string $uri, $action, string $name = '' ) {
		return $this->add_route( [ 'PATCH' ], $uri, $action, $name );
	}

	public function options( string $uri, $action, string $name = '' ) {
		return $this->add_route( [ 'OPTIONS' ], $uri, $action, $name );
	}

	protected function add_route( array $methods, string $uri, $action, string $name = '' ) {
		$route = new Route( $uri, $this->handle_action( $action ) );

		if ( ! empty( $name ) ) {
			$name = is_array( $action ) ? $action['name'] ?? '' : '';

			if ( empty( $name ) ) {
				$name = implode( '.', $methods ) . ":{$uri}";
			}
		}

		$this->routes->add( $name, $route );
		return $this;
	}

	protected function handle_action( $action ): array {
		if ( is_string( $action ) ) {
			return [
				'action' => $action,
			];
		}

		return (array) $action;
	}
}
