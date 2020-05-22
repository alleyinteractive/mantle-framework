<?php
/**
 * Mantle Framework Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @package Mantle
 */

use Mantle\Framework\Application;

/**
 * Get the available container instance.
 *
 * @param string|null $abstract Component name.
 * @param array       $parameters Parameters to pass to the class.
 * @return mixed|Mantle\Framework\Application
 */
function mantle_app( string $abstract = null, array $parameters = [] ) {
	if ( empty( $abstract ) ) {
		return Application::getInstance();
	}

	return Application::getInstance()->make( $abstract, $parameters );
}

/**
 * Get a configuration value from the Configuration Repository.
 *
 * @param string $key Key to retrieve.
 * @param mixed  $default Default configuration value.
 * @return mixed
 */
function mantle_config( string $key = null, $default = null ) {
	if ( is_null( $key ) ) {
		return mantle_app( 'config' );
	}

	return mantle_app( 'config' )->get( $key, $default );
}

/**
 * Get the base path to the application.
 *
 * @param string $path Path to append.
 * @return string
 */
function mantle_base_path( string $path = '' ): string {
	return mantle_app()->get_base_path( $path );
}
