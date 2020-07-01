<?php
/**
 * This file contains the Admin_Screen trait
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Concerns;

/**
 * This trait, when used, sets the current screen so that `is_admin()` is true.
 */
trait Admin_Screen {
	/**
	 * Backed up screen.
	 *
	 * @var \WP_Screen|null
	 */
	protected $backup_screen;

	/**
	 * Backup the current screen.
	 */
	public function admin_screen_set_up() {
		$this->backup_screen = get_current_screen();
		set_current_screen( 'dashboard-user' );
	}

	/**
	 * Restore the backed up screen.
	 */
	public function admin_screen_tear_down() {
		// Restore current_screen.
		set_current_screen( $this->backup_screen );
	}
}
