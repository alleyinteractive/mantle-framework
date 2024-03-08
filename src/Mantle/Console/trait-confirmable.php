<?php
/**
 * Confirmable trait file.
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Mantle\Console\Concerns\Interacts_With_IO;

/**
 * Checks if the command needs to be confirmed before proceeding.
 *
 * Useful to allow dangerous commands to gut-check if they should be
 * run on production environments.
 */
trait Confirmable {
	use Interacts_With_IO;

	/**
	 * Confirm before proceeding with the action.
	 * This method only asks for confirmation in production.
	 *
	 * @todo Add CLI flag to allow for bypass.
	 *
	 * @param string $warning Warning to the user.
	 * @return bool True to proceed, false otherwise.
	 */
	public function confirm_to_proceed( string $warning = null ): bool {
		// Check if the command needs to be confirmed.
		if ( 'production' !== app()->environment() ) {
			return true;
		}

		$this->line( $warning ?? __( 'Application In Production!', 'mantle' ) );

		$confirm = $this->confirm( __( 'Do you really wish to run this command?', 'mantle' ) );

		if ( ! $confirm ) {
			$this->line( __( 'Command Cancelled!', 'mantle' ) );
			return false;
		}

		return true;
	}
}
