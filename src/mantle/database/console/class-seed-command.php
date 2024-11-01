<?php
/**
 * Seed_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Console;

use Mantle\Console\Command;
use Mantle\Console\Confirmable;

use function Mantle\Support\Helpers\remove_action_validated;

/**
 * Database Seed Command
 */
class Seed_Command extends Command {
	use Confirmable;

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = 'db:seed {--class=}';

	/**
	 * Run Database Seeding
	 */
	public function handle(): int {
		if ( ! $this->confirm_to_proceed() ) {
			return static::FAILURE;
		}

		// Disable cache purging.
		if ( class_exists( 'WPCOM_VIP_Cache_Manager' ) ) {
			remove_action_validated( 'shutdown', [ \WPCOM_VIP_Cache_Manager::instance(), 'execute_purges' ] );
		}

		$class = $this->container->get_namespace() . '\\Database\\Seeds\\Database_Seeder';

		if ( ! class_exists( $class ) ) {
			$this->error( "Database Seeder class not found: {$class}" );
			return static::FAILURE;
		}

		$this->container
			->make( $this->option( 'class', \App\Database\Seeds\Database_Seeder::class ) )
			->set_container( $this->container )
			->set_command( $this )
			->__invoke();

		$this->success( __( 'Database seeding completed.', 'mantle' ) );

		return static::SUCCESS;
	}
}
