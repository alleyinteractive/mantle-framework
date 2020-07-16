<?php
/**
 * Router interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Http\Routing;

use Mantle\Framework\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
}
