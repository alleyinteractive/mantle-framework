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

use Mantle\Assets\Asset_Loader;
use Mantle\Assets\Mix;
use Mantle\Assets\Asset_Manager;

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

if ( ! function_exists( 'asset_loader' ) ) {
	/**
	 * Retrieve an instance of the Asset Loader OR the URL for a given asset.
	 *
	 * @return \Mantle\Assets\Asset_Loader|string|null Returns the asset loader instance or the URL if a path is provided.
	 */
	function asset_loader( ?string $path = null ): Asset_Loader|string|null {
		if ( $path ) {
			return app( Asset_Loader::class )->url( $path );
		}

		return app( Asset_Loader::class );
	}
}
