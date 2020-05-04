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
	 */
	public function handle() {
		if ( ! $this->confirm_to_proceed() ) {
			return;
		}

		\WP_CLI::log( 'Handle..' );
	}
}
