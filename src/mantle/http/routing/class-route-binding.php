<?php
/**
 * Route_Binding class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Closure;
use Mantle\Contracts\Container;
use Mantle\Database\Model\Model_Not_Found_Exception;
use Mantle\Support\Str;

/**
 * Route Binding to support model-resolution in a route.
 */
class Route_Binding {
	/**
	 * Create a Route model binding for a given callback.
	 *
	 * @param Container       $container Container instance.
	 * @param \Closure|string $binder Route binding.
	 * @return \Closure
	 */
	public static function for_callback( $container, $binder ) {
		if ( is_string( $binder ) ) {
			return static::create_class_binding( $container, $binder );
		}

		return $binder;
	}

	/**
	 * Create a class based binding using the IoC container.
	 *
	 * @param  Container $container
	 * @param  string    $binding Binding name.
	 * @return \Closure
	 */
	protected static function create_class_binding( Container $container, $binding ) {
		return function ( $value, $route ) use ( $container, $binding ) {
			// If the binding has an @ sign, we will assume it's being used to delimit
			// the class name from the bind method name. This allows for bindings
			// to run multiple bind methods in a single class for convenience.
			[ $class, $method ] = Str::parse_callback( $binding, 'bind' );

			$callable = [ $container->make( $class ), $method ];

			return $callable( $value, $route );
		};
	}

	/**
	 * Create a Route model binding for a model.
	 *
	 * @param  Container     $container Container instance.
	 * @param  string        $class Class name.
	 * @param  \Closure|null $callback Callback for binding.
	 * @return \Closure
	 *
	 * @throws Model_Not_Found_Exception Thrown on missing model.
	 */
	public static function for_model( Container $container, $class, $callback = null ) {
		return function ( $value ) use ( $container, $class, $callback ) {
			if ( is_null( $value ) ) {
				return;
			}

			// For model binders, we will attempt to retrieve the models using the first
			// method on the model instance. If we cannot retrieve the models we'll
			// throw a not found exception otherwise we will return the instance.
			$instance = $container->make( $class );

			$model = $instance->resolve_route_binding( $value );
			if ( $model ) {
				return $model;
			}

			// If a callback was supplied to the method we will call that to determine
			// what we should do when the model is not found. This just gives these
			// developer a little greater flexibility to decide what will happen.
			if ( $callback instanceof Closure ) {
				return $callback( $value );
			}

			throw ( new Model_Not_Found_Exception() )->set_model( $class );
		};
	}
}
