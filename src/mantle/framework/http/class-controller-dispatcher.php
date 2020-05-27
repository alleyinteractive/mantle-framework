<?php
/**
 * Controller class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http;

use Mantle\Framework\Container\Container;
use Mantle\Framework\Facade\Route;

/**
 * Controller Dispatcher
 */
class Controller_Dispatcher {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Dispatch a request to a given controller and method.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  mixed  $controller
	 * @param  string  $method
	 * @return mixed
	 */
	public function dispatch( Route $route, $controller, $method) {
		$parameters = $this->resolveClassMethodDependencies(
				$route->parametersWithoutNulls(), $controller, $method
		);

		if ( method_exists( $controller, 'call_action' ) ) {
			return $controller->call_action( $method, $parameters );
		}

		return $controller->{ $method }( ...array_values( $parameters ) );
	}
}
