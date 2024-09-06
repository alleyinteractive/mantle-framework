<?php
/**
 * Run_In_Parallel trait file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Utils;

/**
 * Concern for interacting with parallel tests with paratest.
 *
 * @mixin \Mantle\Testing\Test_Case
 */
trait Runs_In_Parallel {
	/**
	 * Run a callback only when testing in parallel.
	 *
	 * @param callable $callback The callback to run.
	 */
	public function when_running_in_parallel( callable $callback ): void {
		if ( Utils::is_parallel() ) {
			$callback();
		}
	}

	/**
	 * Get the parallel token.
	 */
	public function parallel_token(): ?string {
		return Utils::parallel_token();
	}
}
