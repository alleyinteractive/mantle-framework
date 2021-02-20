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
	 * @param \Closure|string $route Route to register or a callback function that
	 *                               will register child REST API routes.
	 * @param array           $args Arguments for the route or callback for the route.
	 *                              Not used if $route is a callback.
	 */
	public function rest_api( string $namespace, $route, $args = [] );

	/**
	 * Rename a route.
	 *
	 * @param string $old_name Old route name.
	 * @param string $new_name New route name.
	 * @return static
	 *
	 * @throws InvalidArgumentException Thrown when attempting to rename a route
	 *                                  a name that is already taken.
	 */
	public function rename_route( string $old_name, string $new_name );
}
