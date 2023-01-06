<?php
/**
 * Mantle Assets Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

declare( strict_types=1 );

use Mantle\Assets\Mix;
use Mantle\Assets\Asset_Manager;

if ( ! function_exists( 'mix' ) ) {
	/**
	 * Get the path to a versioned Mix file or a Mix instance.
	 *
	 * @param  string  $path Path to the asset, optional.
	 * @param  string  $manifest_directory Path to the manifest directory, optional.
	 * @return string|Mix
	 *
	 * @throws \Exception
	 */
	function mix( string $path = null, string $manifest_directory = null ) {
		if ( ! $path ) {
			return app( Mix::class );
		}

		return app( Mix::class )( ...func_get_args() );
	}
}

if ( ! function_exists( 'asset' ) ) {
	/**
	 * Retrieve an instance of the Asset Manager.
	 *
	 * @return \Mantle\Assets\Asset_Manager
	 */
	function asset(): Asset_Manager {
		return app( Asset_Manager::class );
	}
}
