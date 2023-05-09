<?php
/**
 * Mantle Framework Application Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

declare( strict_types=1 );

use Mantle\Application\Application;
use Mantle\Support\Environment;

if ( ! function_exists( 'app' ) ) {
	/**
	 * Get the available container instance.
	 *
	 * @param string|null $abstract Abstract to resolve.
	 * @param array<mixed> $parameters Parameters.
	 * @return mixed|Application
	 */
	function app( string $abstract = null, array $parameters = [] ) {
		if ( empty( $abstract ) ) {
			return Application::getInstance();
		}

		return Application::getInstance()->make( $abstract, $parameters );
	}
}

if ( ! function_exists( 'environment' ) ) {
	/**
	 * Gets the value of an environment variable.
	 *
	 * @see Mantle\Support\Environment
	 *
	 * @param  string $key Environment variable key.
	 * @param  mixed  $default Default value.
	 * @return mixed
	 */
	function environment( string $key, $default = null ) {
		return Environment::get( $key, $default );
	}
}

if ( ! function_exists( 'base_path' ) ) {
	/**
	 * Get the base path to the application.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	function base_path( string $path = '' ): string {
		return app()->get_base_path( $path );
	}
}

if ( ! function_exists( 'app_path' ) ) {
	/**
	 * Get the application path (the app/ folder).
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	function app_path( string $path = '' ): string {
		return app()->get_app_path( $path );
	}
}

if ( ! function_exists( 'storage_path' ) ) {
	/**
	 * Get the path to the storage folder.
	 *
	 * @param  string  $path Path to append.
	 * @return string
	 */
	function storage_path( string $path = '' ): string {
			return app( 'path.storage' ) . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}
}
