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
 * @method static \Mantle\Http\Routing\Route get( string $uri, $action = '' )
 * @method static \Mantle\Http\Routing\Route post( string $uri, $action = '' )
 * @method static \Mantle\Http\Routing\Route put( string $uri, $action = '' )
 * @method static \Mantle\Http\Routing\Route delete( string $uri, $action = '' )
 * @method static \Mantle\Http\Routing\Route patch( string $uri, $action = '' )
 * @method static \Mantle\Http\Routing\Route options( string $uri, $action = '' )
 * @method static \Mantle\Http\Routing\Router alias_middleware( string $name, string $class )
 * @method static \Mantle\Http\Routing\Router middleware_group( string $name, array $middleware )
 * @method static \Mantle\Http\Routing\Router prepend_middleware_to_group( $group, $middleware )
 * @method static \Mantle\Http\Routing\Router push_middleware_to_group( $group, $middleware )
 * @method static void bind( string $key, $binder )
 * @method static void bind_model( $key, $class, Closure $callback = null )
 * @method static \Mantle\Http\Routing\Rest_Route_Registrar rest_api( string $namespace, $route, $args = [] )
 * @method static void model( string $model, string $controller ): void
 * @method static \Mantle\Http\Routing\Route_Registrar middleware( array|string $middleware )
 * @method static \Mantle\Http\Routing\Router rename_route( string $name, string $new_name )
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
