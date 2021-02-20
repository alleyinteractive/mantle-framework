<?php
/**
 * Route_Matched class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Events;

use Mantle\Http\Request;
use Mantle\Http\Routing\Route;

/**
 * Event for route matched event.
 */
class Route_Matched {
	/**
	 * Route matched.
	 *
	 * @var Route|array
	 */
	public $route;

	/**
	 * Current request.
	 *
	 * @var \Mantle\Http\Request
	 */
	public $request;

	/**
	 * Constructor.
	 *
	 * @param Route|array $route Route matched, Mantle route or an array of route
	 *                           information for REST API routes.
	 * @param Request     $request Current request.
	 */
	public function __construct( $route, $request ) {
		$this->route   = $route;
		$this->request = $request;
	}
}
