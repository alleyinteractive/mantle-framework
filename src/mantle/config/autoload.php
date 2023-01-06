<?php
/**
 * Mantle Config Application Helpers
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

if ( ! function_exists( 'config' ) ) {
	/**
	 * Get a configuration value from the Configuration Repository.
	 *
	 * @param string $key Key to retrieve.
	 * @param mixed  $default Default configuration value.
	 * @return mixed
	 */
	function config( string $key = null, $default = null ) {
		if ( is_null( $key ) ) {
			return Application::getInstance()->make( 'config' );
		}

		return Application::getInstance()->make( 'config' )->get( $key, $default );
	}
}
