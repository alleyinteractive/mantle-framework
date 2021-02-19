<?php
/**
 * Kernel interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Console;

/**
 * Console Kernel
 */
interface Kernel {
	/**
	 * Run the console application.
	 */
	public function handle();

	/**
	 * Register the application's commands.
	 */
	public function register_commands();

	/**
	 * Log to the console.
	 *
	 * @param string $message Message to log.
	 */
	public function log( string $message );
}
