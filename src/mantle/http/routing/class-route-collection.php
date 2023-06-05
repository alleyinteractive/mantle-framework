<?php

namespace Mantle\Http\Routing;

use Symfony\Component\Routing\RouteCollection;

class Route_Collection extends RouteCollection {
	/**
	 * Add a route to the collection.
	 *
	 * @param string $name
	 * @param Route $route
	 * @param integer $property
	 * @return Route
	 */
	public function add( string $name, Route $route, int $property = 0 ): Route {
		parent::add( $name, $route, $property );

		return $route;
	}
}
