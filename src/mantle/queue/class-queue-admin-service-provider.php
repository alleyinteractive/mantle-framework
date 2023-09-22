<?php
/**
 * Queue_Admin_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Contracts\Queue\Queue_Manager as Queue_Manager_Contract;
use Mantle\Queue\Console\Run_Command;
use Mantle\Queue\Dispatcher;
use Mantle\Queue\Queue_Manager;
use Mantle\Queue\Worker;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Service_Provider;

use function Mantle\Support\Helpers\tap;

/**
 * Queue Admin Service Provider
 *
 * Provides a UI for displaying the queue.
 */
class Queue_Admin_Service_Provider extends Service_Provider {
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
			__( 'Mantle Queue', 'mantle' ),
			__( 'Mantle Queue', 'mantle' ),
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
		$table = new Admin\Queue_Jobs_Table();

		$table->prepare_items();

		include __DIR__ . '/template/admin.php';
	}
}
