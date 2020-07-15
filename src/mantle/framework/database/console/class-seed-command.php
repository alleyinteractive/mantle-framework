<?php
/**
 * Seed_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Console;

use Mantle\Framework\Console\Command;
use Mantle\Framework\Console\Confirmable;
use Mantle\Framework\Contracts\Application;

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
	 * Command synopsis.
	 *
	 * @var string|array
	 */
	protected $synopsis = '[--class=<class>]';

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param Application $app
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

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

		$this->app
			->make( $this->get_flag( 'class', \App\Database\Seeds\Database_Seeder::class ) )
			->set_container( $this->app )
			->set_command( $this )
			->__invoke();
	}
}
