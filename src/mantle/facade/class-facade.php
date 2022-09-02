<?php
/**
 * Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Mantle\Contracts\Application;
use RuntimeException;

/**
 * Mantle Facade
 *
 * Provides a static interface to classes provided by the application's
 * service container. Provides a simple static interface to core functionality
 * and promotes ease of testability.
 */
abstract class Facade {
	/**
	 * The application instance being facaded.
	 *
	 * @var \Mantle\Contracts\Application|\Mantle\Contracts\Container
	 */
	protected static $app;

	/**
	 * The resolved object instances.
	 *
	 * @var array
	 */
	protected static $resolved_instances;

	/**
	 * Get the name of the Facade Accessor
	 *
	 * @return string
	 */
	abstract protected static function get_facade_accessor(): string;

	/**
	 * Clear the resolved instances by the Facade.
	 */
	public static function clear_resolved_instances() {
		static::$resolved_instances = [];
	}

	/**
	 * Clear a resolved facade instance.
	 *
	 * @param string $name Instance name.
	 */
	public static function clear_resolved_instance( string $name ) {
		unset( static::$resolved_instances[ $name ] );
	}

	/**
	 * Set the application instance for the Facade.
	 *
	 * @param \Mantle\Contracts\Application $app Application instance.
	 */
	public static function set_facade_application( Application $app = null ) {
		static::$app = $app;
	}

	/**
	 * Hotswap the underlying instance behind the facade.
	 *
	 * @param mixed $instance Object instance.
	 */
	public static function swap( $instance ) {
		static::$resolved_instances[ static::get_facade_accessor() ] = $instance;

		if ( isset( static::$app ) ) {
			static::$app->instance( static::get_facade_accessor(), $instance );
		}
	}

	/**
	 * Get the Facade's Root Instance
	 *
	 * @return mixed
	 */
	protected static function get_facade_root() {
		return static::resolve_facade_instance( static::get_facade_accessor() );
	}

	/**
	 * Resolve the Facade Instance with the Application's Container
	 *
	 * @param string $name Name of the Facade Accessor.
	 * @return mixed
	 */
	protected static function resolve_facade_instance( $name ) {
		if ( is_object( $name ) ) {
			return $name;
		}

		if ( isset( static::$resolved_instances[ $name ] ) ) {
			return static::$resolved_instances[ $name ];
		}

		if ( static::$app ) {
			static::$resolved_instances[ $name ] = static::$app[ $name ];
			return static::$resolved_instances[ $name ];
		}
	}

	/**
	 * Handle dynamic, static calls to the object.
	 *
	 * @param  string $method Method invoked.
	 * @param  array  $args Arguments to pass to the method.
	 * @return mixed
	 *
	 * @throws \RuntimeException Throw on missing Facade root.
	 */
	public static function __callStatic( $method, $args ) {
		$instance = static::get_facade_root();

		if ( ! $instance ) {
			throw new RuntimeException( 'A facade root has not been set.' );
		}

		return $instance->$method( ...$args );
	}
}
