<?php
/**
 * Route Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Route Facade
 *
 * @method static public get( string $uri, $action = '' )
 * @method static public post( string $uri, $action = '' )
 * @method static public put( string $uri, $action = '' )
 * @method static public delete( string $uri, $action = '' )
 * @method static public patch( string $uri, $action = '' )
 * @method static public options( string $uri, $action = '' )
 * @method static protected prefix( string $uri )
 */
class Route extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'router';
	}
}
