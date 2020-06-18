<?php
/**
 * Init interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Providers;

/**
 * Register a service provider that will execute a callback on 'init'.
 */
interface Init {
	/**
	 * 'init' callback function.
	 */
	public function on_init();
}
