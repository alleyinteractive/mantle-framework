<?php
/**
 * Asset_Manager class file.
 *
 * phpcs:disable Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use Asset_Manager_Preload;
use Asset_Manager_Scripts;
use Mantle\Contracts\Assets\Asset_Manager as Asset_Manager_Contract;
use Mantle\Contracts\Assets\Load_Method;

use function Mantle\Support\Helpers\hook_callable;

/**
 * Asset Manager
 */
class Asset_Manager implements Asset_Manager_Contract {
	/**
	 * Load a external script.
	 *
	 * @param string          $handle Script handle.
	 * @param string          $src Script URL, optional.
	 * @param string[]|string $deps Script dependencies, optional.
	 * @param array|string    $condition Condition to load, defaults to global.
	 * @param string          $load_method Load method.
	 * @param string          $load_hook Load hook.
	 * @param string|null     $version Script version.
	 * @return Asset
	 */
	public function script( ...$params ) {
		return new Asset( 'script', ...$params );
	}

	/**
	 * Load an external stylesheet file.
	 *
	 * @param string          $handle Stylesheet handle.
	 * @param string          $src Stylesheet URL, optional.
	 * @param string[]|string $deps Stylesheet dependencies.
	 * @param array|string    $condition Condition to load.
	 * @param string          $load_method Load method.
	 * @param string          $load_hook Load hook.
	 * @param string|null     $version Script version.
	 * @return void
	 */
	public function style( ...$params ): Asset {
		return new Asset( 'style', ...$params );
	}

	/**
	 * Preload content by URL.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types/preload
	 *
	 * @param string      $handle Preload handle.
	 * @param string      $src URL to preload.
	 * @param string      $condition Condition to preload.
	 * @param string|null $as Preload as, defaults to detect "as" by file URL.
	 * @param string|null $mime_type Mime type to load as, defaults to detect by file URL.
	 * @param string      $media Media to preload, defaults to 'all'.
	 * @param bool        $crossorigin Flag to load as cross origin, defaults to false.
	 * @param string|null $version Handle version, optional.
	 * @return void
	 */
	public function preload(
		string $handle,
		string $src,
		$condition = 'global',
		?string $as = null,
		?string $mime_type = null,
		string $media = 'all',
		bool $crossorigin = false,
		?string $version = null
	): void {
		hook_callable(
			'wp_enqueue_scripts',
			fn() => Asset_Manager_Preload::instance()->add_asset(
				[
					'handle'      => $handle,
					'src'         => $src,
					'condition'   => $condition,
					'version'     => $version,
					'media'       => $media,
					'as'          => $as,
					'crossorigin' => $crossorigin,
					'mime_type'   => $mime_type,
				]
			),
		);
	}

	/**
	 * Asynchronously load a script file.
	 *
	 * @param string $handle Handle to change.
	 * @return void
	 */
	public function async( string $handle ): void {
		hook_callable(
			'wp_enqueue_scripts',
			fn() => Asset_Manager_Scripts::instance()->modify_load_method( $handle, Load_Method::ASYNC ),
			20, // Ensures the asset is registered.
		);
	}

	/**
	 * Defer a script file
	 *
	 * @param string $handle Handle to change.
	 * @return void
	 */
	public function defer( string $handle ): void {
		hook_callable(
			'wp_enqueue_scripts',
			fn() => Asset_Manager_Scripts::instance()->modify_load_method( $handle, Load_Method::DEFER ),
			20, // Ensures the asset is registered.
		);
	}

	/**
	 * Change the load method of an asset.
	 *
	 * @param string $handle Handle to change.
	 * @param string $load_method Load method to change to.
	 * @return void
	 */
	public function load_method( string $handle, string $load_method = Load_Method::SYNC ): void {
		hook_callable(
			'wp_enqueue_scripts',
			fn() => Asset_Manager_Scripts::instance()->modify_load_method( $handle, $load_method ),
			20, // Ensures the asset is registered.
		);
	}
}
