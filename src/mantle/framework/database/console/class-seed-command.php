<?php
/**
 * Seed_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Console;

use Mantle\Framework\Console\Command;
use Mantle\Framework\Console\Confirmable;

/**
 * Database Seed Command
 */
class Seed_Command extends Command {
	use Confirmable;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'db:seed';

	/**
	 * Run Database Seeding
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args ) {
		if ( ! $this->confirm_to_proceed() ) {
			return;
		}

		$this->log( 'Handle...' );
	}
}
