<?php
/**
 * Url_Generator class file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\Routing;

/**
 * URL Generator
 */
interface Url_Generator {
	/**
	 * Get the current URL for the request.
	 *
	 * @return string
	 */
	public function current();

	/**
	 * Get the URL for the previous request.
	 *
	 * @param string $fallback Fallback value, optional.
	 * @return string
	 */
	public function previous( string $fallback = null ): string;

	/**
	 * Generate a URL to a specific path.
	 *
	 * @param string $path URL Path.
	 * @param array  $extra Extra parameters.
	 * @param bool   $secure Flag if should be forced to be secure.
	 * @return string
	 */
	public function to( string $path, array $extra = [], bool $secure = null );
}
