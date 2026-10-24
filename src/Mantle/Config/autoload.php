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

declare(strict_types=1);

use Mantle\Support\Mixed_Data;

if ( ! function_exists( 'config' ) ) {
	/**
	 * Get a configuration value from the Configuration Repository.
	 *
	 * @param string|null $key Key to retrieve.
	 * @param mixed       $default Default configuration value.
	 * @phpstan-return ($key is null ? \Mantle\Config\Repository : mixed)
	 */
	function config( ?string $key = null, mixed $default = null ): mixed {
		/** @var \Mantle\Config\Repository $config */
		$config = app( 'config' );

		return is_null( $key ) ? $config : $config->get( $key, $default );
	}
}

if ( ! function_exists( 'config_mixed' ) ) {
	/**
	 * Get a configuration value from the Configuration Repository as a mixed data object.
	 *
	 * @param string $key Key to retrieve.
	 * @param mixed  $default Default configuration value.
	 */
	function config_mixed( string $key, mixed $default = null ): Mixed_Data {
		return config()->get_mixed( $key, $default );
	}
}
