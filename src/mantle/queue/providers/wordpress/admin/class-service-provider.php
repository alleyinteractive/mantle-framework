<?php
/**
 * Admin_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress\Admin;

use Mantle\Support\Attributes\Action;
use Mantle\Support\Service_Provider as Base_Service_Provider;

/**
 * Queue Admin Service Provider
 *
 * Provides a UI for displaying the WordPress queue.
 */
class Service_Provider extends Base_Service_Provider {
	/**
	 * Register the service provider.
	 */
	public function register() {

	}

	/**
	 * Register the admin submenu page.
	 */
	#[Action( 'admin_menu' )]
	public function register_admin_page(): void {
		add_submenu_page(
			'tools.php',
			__( 'Queue', 'mantle' ),
			__( 'Queue', 'mantle' ),
			/**
			 * Filter the capability required to view the queue admin page.
			 *
			 * @param string $capability The capability required to view the queue admin page.
			 */
			apply_filters( 'mantle_queue_admin_capability', 'manage_options' ),
			'mantle-queue',
			[ $this, 'render_admin_page' ],
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page(): void {
		$table = new Queue_Jobs_Table();

		$table->prepare_items();

		include __DIR__ . '/template/admin.php';
	}
}
