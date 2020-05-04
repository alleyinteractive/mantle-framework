<?php
/**
 * Seed_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Console;

use Mantle\Framework\Console\Command;
use Mantle\Framework\Console\Conformable;

/**
 * Database Seed Command
 */
class Seed_Command extends Command {
	use Conformable;

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
