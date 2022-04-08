<?php
/**
 * Seed_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Console;

use Mantle\Console\Command;
use Mantle\Console\Confirmable;
use Mantle\Contracts\Application;

use function Mantle\Support\Helpers\remove_action_validated;

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
	public function handle( array $args, array $assoc_args = [] ) {
		if ( ! $this->confirm_to_proceed() ) {
			return;
		}

		// Disable cache purging.
		if ( class_exists( 'WPCOM_VIP_Cache_Manager' ) ) {
			remove_action_validated( 'shutdown', [ \WPCOM_VIP_Cache_Manager::instance(), 'execute_purges' ] );
		}

		$this->app
			->make( $this->get_flag( 'class', \App\Database\Seeds\Database_Seeder::class ) )
			->set_container( $this->app )
			->set_command( $this )
			->__invoke();
	}
}
