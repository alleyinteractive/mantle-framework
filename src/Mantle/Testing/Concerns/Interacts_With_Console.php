<?php
/**
 * Interacts_With_Console trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Console\Command;
use Mantle\Testing\Test_Command;

/**
 * Allow console commands to be tested against.
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Interacts_With_Console {
	/**
	 * Create a new Test_Command instance and run it.
	 *
	 * @throws \RuntimeException If the application instance is not available.
	 *
	 * @param string       $command Command to run.
	 * @param array<mixed> $args     Arguments to pass to the command.
	 */
	public function command( string $command, array $args = [] ): Test_Command {
		if ( ! $this->app ) {
			throw new \RuntimeException( 'The application instance is not available.' );
		}

		return new Test_Command( $this, $this->app, $command, $args );
	}
}
