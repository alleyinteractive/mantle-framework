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

if ( ! function_exists( 'config' ) ) {
	/**
	 * Get a configuration value from the Configuration Repository.
	 *
	 * @param string|null $key Key to retrieve.
	 * @param mixed       $default Default configuration value.
	 */
	function config( ?string $key = null, mixed $default = null ): mixed {
		if ( is_null( $key ) ) {
			return app( 'config' );
		}

		return app( 'config' )->get( $key, $default );
	}
}
