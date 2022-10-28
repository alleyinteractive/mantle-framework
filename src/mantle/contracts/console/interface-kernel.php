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
	 *
	 * @param mixed      $input Console input.
	 * @param mixed|null $output Console output.
	 * @return int
	 */
	public function handle( $input, $output = null );

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

	/**
	 * Terminate the application.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface $input
	 * @param  int                                             $status
	 * @return void
	 */
	public function terminate( $input, $status ): void;
}
