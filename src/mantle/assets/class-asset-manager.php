<?php
/**
 * Asset_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use Asset_Manager_Preload;
use Asset_Manager_Scripts;
use Asset_Manager_Styles;
use Mantle\Contracts\Assets\Asset_Manager as Asset_Manager_Contract;
use Mantle\Contracts\Assets\Load_Hook;
use Mantle\Contracts\Assets\Load_Method;

/**
 * Asset Manager
 */
class Asset_Manager implements Asset_Manager_Contract {
	/**
	 * Load a external script.
	 *
	 * @param string          $handle Script handle.
	 * @param string          $src Script URL.
	 * @param string[]|string $deps Script dependencies.
	 * @param array|string    $condition Condition to load.
	 * @param string          $load_method Load method.
	 * @param string          $load_hook Load hook.
	 * @param string|null     $version Script version.
	 * @return void
	 */
	public function script(
		string $handle,
		string $src,
		array $deps = [],
		$condition = 'global',
		string $load_method = Load_Method::SYNC,
		string $load_hook = Load_Hook::HEADER,
		?string $version = null
	): void {
		Asset_Manager_Scripts::instance()->add_asset(
			[
				'handle'      => $handle,
				'src'         => $src,
				'deps'        => $deps,
				'condition'   => $condition,
				'load_method' => $load_method,
				'version'     => $version,
				'load_hook'   => $load_hook,
			]
		);
	}

	/**
	 * Load an external stylesheet file.
	 *
	 * @param string          $handle Stylesheet handle.
	 * @param string          $src Stylesheet URL.
	 * @param string[]|string $deps Stylesheet dependencies.
	 * @param array|string    $condition Condition to load.
	 * @param string          $load_method Load method.
	 * @param string          $load_hook Load hook.
	 * @param string|null     $version Script version.
	 * @param string          $media Style media.
	 * @return void
	 */
	public function style(
		string $handle,
		string $src,
		array $deps = [],
		$condition = 'global',
		string $load_method = Load_Method::SYNC,
		string $load_hook = Load_Hook::HEADER,
		?string $version = null,
		string $media = null
	): void {
		Asset_Manager_Styles::instance()->add_asset(
			[
				'handle'      => $handle,
				'src'         => $src,
				'deps'        => $deps,
				'condition'   => $condition,
				'load_method' => $load_method,
				'version'     => $version,
				'load_hook'   => $load_hook,
				'media'       => $media ?: null,
			]
		);
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
		Asset_Manager_Preload::instance()->add_asset(
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
		);
	}

	/**
	 * Asynchronously load a script file.
	 *
	 * @param string $handle Handle to change.
	 * @return void
	 */
	public function async( string $handle ): void {
		Asset_Manager_Scripts::instance()->modify_load_method( $handle, Load_Method::ASYNC );
	}

	/**
	 * Defer a script file
	 *
	 * @param string $handle Handle to change.
	 * @return void
	 */
	public function defer( string $handle ): void {
		Asset_Manager_Scripts::instance()->modify_load_method( $handle, Load_Method::DEFER );
	}

	/**
	 * Change the load method of an asset.
	 *
	 * @param string $handle Handle to change.
	 * @param string $load_method Load method to change to.
	 * @return void
	 */
	public function load_method( string $handle, string $load_method = Load_Method::SYNC ): void {
		Asset_Manager_Scripts::instance()->modify_load_method( $handle, $load_method );
	}
}
