<?php
/**
 * Kernel interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Http;

/**
 * Http Kernel
 */
interface Kernel {
	/**
	 * Run the HTTP Application.
	 */
	public function handle();
}
