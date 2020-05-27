<?php
/**
 * Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Contracts\Http\Routing\Router as Router_Contract;
use Mantle\Framework\Http\Http_Exception;
use Mantle\Framework\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Router
 *
 * Allow registration of routes to the application.
 */
class Router implements Router_Contract {
	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Route Collection
	 *
	 * @var RouteCollection
	 */
	protected $routes;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app    = $app;
		$this->routes = new RouteCollection();
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function get( string $uri, $action = '' ) {
		return $this->add_route( [ 'GET', 'HEAD' ], $uri, $action );
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function post( string $uri, $action = '' ) {
		return $this->add_route( [ 'POST' ], $uri, $action );
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function put( string $uri, $action = '' ) {
		return $this->add_route( [ 'PUT' ], $uri, $action );
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function delete( string $uri, $action = '' ) {
		return $this->add_route( [ 'DELETE' ], $uri, $action );
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function patch( string $uri, $action = '' ) {
		return $this->add_route( [ 'PATCH' ], $uri, $action );
	}

	/**
	 * Register a OPTIONS route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function options( string $uri, $action = '' ) {
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
	 * Get registered routes.
	 *
	 * @return RouteCollection
	 */
	public function get_routes(): RouteCollection {
		return $this->routes;
	}

	/**
	 * Dispatch a request to the registered routes.
	 *
	 * @param Request $request Request object.
	 * @return Response|null
	 */
	public function dispatch( Request $request ): ?Response {
		return $this->execute_route_match(
			$this->match_route( $request )
		);
	}

	/**
	 * Match a request to a registered route.
	 *
	 * @param Request $request Request object.
	 * @return array|null
	 */
	protected function match_route( Request $request ) {
		$context = new RequestContext();
		$context = $context->fromRequest( $request );
		$matcher = new UrlMatcher( $this->get_routes(), $context );

		return $matcher->matchRequest( $request );
	}

	/**
	 * Execute a route match and retrieve the response.
	 *
	 * @param array $match Route match.
	 * @return Response|null
	 *
	 * @throws Http_Exception Thrown on unknown route callback.
	 */
	protected function execute_route_match( $match ): ?Response {
		$route = Route::get_route_from_match( $match );

		if ( $route ) {
			return $route->run( $this->app );
		}

		throw new Http_Exception( 'Unknown route method: ' . \wp_json_encode( $match ) );
	}
}
