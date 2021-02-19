<?php
/**
 * Wp_Loaded interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Providers;

/**
 * Register a service provider that will execute a callback on 'wp_loaded'.
 */
interface Wp_Loaded {
	/**
	 * 'wp_loaded' callback function.
	 */
	public function on_wp_loaded();
}
