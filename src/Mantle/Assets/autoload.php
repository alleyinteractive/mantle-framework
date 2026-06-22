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

declare(strict_types=1);

use Mantle\Assets\Asset_Loader;
use Mantle\Assets\Asset_Manager;

if ( ! function_exists( 'asset' ) ) {
	/**
	 * Retrieve an instance of the Asset Manager.
	 */
	function asset(): Asset_Manager {
		return app()->class( Asset_Manager::class );
	}
}

if ( ! function_exists( 'asset_loader' ) ) {
	/**
	 * Retrieve an instance of the Asset Loader OR the URL for a given asset.
	 *
	 * @template TPath of string|null
	 *
	 * @param string|null $path Optional. The asset path to retrieve the URL for. If null, returns the Asset Loader instance.
	 * @return \Mantle\Assets\Asset_Loader|string|null Returns the asset loader instance or the URL if a path is provided.
	 *
	 * @phpstan-param TPath $path
	 * @phpstan-return (TPath is null ? \Mantle\Assets\Asset_Loader : string|null)
	 */
	function asset_loader( ?string $path = null ): Asset_Loader|string|null {
		if ( $path ) {
			return app()->class( Asset_Loader::class )->url( $path );
		}

		return app()->class( Asset_Loader::class );
	}
}
