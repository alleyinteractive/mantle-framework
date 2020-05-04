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
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Example description';

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
