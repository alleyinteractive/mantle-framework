<?php
/**
 * This file contains the Network_Admin_Screen trait
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Concerns;

/**
 * This trait, when used, sets the current screen so that `is_network_admin()`
 * is true.
 */
trait Network_Admin_Screen {
	/**
	 * Backed up screen.
	 *
	 * @var \WP_Screen|null
	 */
	protected $backup_screen;

	/**
	 * Backup the current screen.
	 */
	public function network_admin_screen_set_up() {
		$this->backup_screen = get_current_screen();
		set_current_screen( 'dashboard-network' );
	}

	/**
	 * Restore the backed up screen.
	 */
	public function network_admin_screen_tear_down() {
		// Restore screen to state at setUp.
		set_current_screen( $this->backup_screen );
	}
}
