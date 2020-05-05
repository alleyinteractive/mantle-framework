<?php
/**
 * Kernel interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Console;

/**
 * Console Kernel
 */
interface Kernel {
	/**
	 * Run the console application.
	 */
	public function handle();
}
