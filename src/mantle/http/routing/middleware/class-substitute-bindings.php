<?php
/**
 * Substitute_Bindings class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Middleware;

use Closure;
use Mantle\Framework\Contracts\Http\Routing\Router;
use Mantle\Http\Request;

/**
 * Substitute parameters for the route with dynamically binded models.
 */
class Substitute_Bindings {
	/**
	 * Router Instance
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Constructor.
	 *
	 * @param Router $router Router instance.
	 */
	public function __construct( Router $router ) {
		$this->router = $router;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param Request  $request Request instance.
	 * @param \Closure $next Callback for the middleware.
	 * @return mixed
	 */
	public function handle( Request $request, Closure $next ) {
		$this->router->substitute_bindings( $request );
		$this->router->substitute_implicit_bindings( $request );

		return $next( $request );
	}
}
