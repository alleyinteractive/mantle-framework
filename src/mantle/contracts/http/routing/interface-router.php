<?php
/**
 * Router interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\Routing;

use Mantle\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;

/**
 * Router Contract
 */
interface Router {
	/**
	 * Register a GET route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function get( string $uri, $action = '' );

	/**
	 * Register a POST route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function post( string $uri, $action = '' );

	/**
	 * Register a PUT route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function put( string $uri, $action = '' );

	/**
	 * Register a DELETE route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function delete( string $uri, $action = '' );

	/**
	 * Register a PATCH route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function patch( string $uri, $action = '' );

	/**
	 * Register a OPTIONS route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function options( string $uri, $action = '' );

	/**
	 * Register a route for any HTTP method.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function any( string $uri, $action = '' );

	/**
	 * Dispatch a request to the registered routes.
	 *
	 * @param Request $request Request object.
	 * @return Response|null
	 */
	public function dispatch( Request $request ): ?Response;

	/**
	 * Get registered routes.
	 *
	 * @return RouteCollection
	 */
	public function get_routes(): RouteCollection;

	/**
	 * Substitute Explicit Bindings
	 *
	 * @param Request $request Request object.
	 */
	public function substitute_bindings( Request $request );

	/**
	 * Substitute the implicit Eloquent model bindings for the route.
	 *
	 * @param Request $request Request instance.
	 */
	public function substitute_implicit_bindings( Request $request );

	/**
	 * Register a REST API route
	 *
	 * @param string          $namespace Namespace for the REST API route.
	 * @param callable|string $callback  Callback that will be invoked to register
	 *                                   routes OR a string route.
	 * @param array           $args      Callback for the route if $callback is a
	 *                                   string route OR arguments to pass to
	 *                                   the register_rest_route() call. Not used if $callback
	 *                                   is a closure.
	 */
	public function rest_api( string $namespace, callable|string $callback, callable|array $args = [] );

	/**
	 * Rename a route.
	 *
	 * @param string $old_name Old route name.
	 * @param string $new_name New route name.
	 * @return static
	 *
	 * @throws \InvalidArgumentException Thrown when attempting to rename a route
	 *                                  a name that is already taken.
	 */
	public function rename_route( string $old_name, string $new_name ): static;

	/**
	 * Register a group of middleware.
	 *
	 * @param  string $name
	 * @param  array  $middleware
	 * @return static
	 */
	public function middleware_group( $name, array $middleware );

	/**
	 * Register a short-hand name for a middleware.
	 *
	 * @param  string $name
	 * @param  string $class
	 * @return static
	 */
	public function alias_middleware( $name, $class );
}
