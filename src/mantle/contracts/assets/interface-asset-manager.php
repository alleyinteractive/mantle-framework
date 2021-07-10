<?php
/**
 * Asset_Manager interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Assets;

/**
 * Asset Manager Contract
 */
interface Asset_Manager {
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
		?string $version = null,
	): void;

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
	): void;

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
	): void;

	/**
	 * Asynchronously load a script file.
	 *
	 * @param string $handle Handle to change.
	 * @return void
	 */
	public function async( string $handle ): void;

	/**
	 * Defer a script file
	 *
	 * @param string $handle Handle to change.
	 * @return void
	 */
	public function defer( string $handle ): void;

	/**
	 * Change the load method of a script.
	 *
	 * @param string $handle Handle to change.
	 * @param string $load_method Load method to change to.
	 * @return void
	 */
	public function script_load_method( string $handle, string $load_method = Load_Method::SYNC ): void;

	/**
	 * Change the load method of a stylesheet.
	 *
	 * @param string $handle Handle to change.
	 * @param string $load_method Load method to change to.
	 * @return void
	 */
	public function style_load_method( string $handle, string $load_method = Load_Method::SYNC ): void;

	/**
	 * Change the load hook of a script.
	 *
	 * @param string $handle Handle to change.
	 * @param string $load_hook Load hook to change to.
	 * @return void
	 */
	public function script_load_hook( string $handle, string $load_hook = Load_Hook::HEADER ): void;

	/**
	 * Change the load hook of a style.
	 *
	 * @param string $handle Handle to change.
	 * @param string $load_hook Load hook to change to.
	 * @return void
	 */
	public function style_load_hook( string $handle, string $load_hook = Load_Hook::HEADER ): void;
}
