<?php
/**
 * Database_Schema trait file
 *
 * phpcs:disable WordPress.DB
 *
 * @package Mantle
 */

namespace Mantle\Queue\Concerns;

use RuntimeException;

use function Mantle\Support\Helpers\option;

/**
 * Manage the database schema for the queue.
 */
trait Database_Queue_Schema {
	/**
	 * The version of the database schema.
	 */
	public const DATABASE_VERSION = 1;

	/**
	 * Create database tables for the queue.
	 *
	 * @throws RuntimeException If the table creation fails.
	 */
	protected function create_tables(): void {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		if ( ! isset( $wpdb->mantle_queue ) ) {
			$wpdb->mantle_queue = $wpdb->prefix . 'mantle_queue'; // @phpstan-ignore-line property.notFound
		}

		$installed_version = option( 'mantle_queue_db_version', 0 )->int();

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		}

		if ( $installed_version < 1 ) {
			if ( ! empty( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->mantle_queue}'" ) ) ) {
				update_option( 'mantle_queue_db_version', self::DATABASE_VERSION );
				return;
			}

			$delta = dbDelta(
				<<<SQL
CREATE TABLE $wpdb->mantle_queue (
	id bigint unsigned NOT NULL AUTO_INCREMENT,
	queue VARCHAR(255) NOT NULL,
	created_date_gmt DATETIME NOT NULL,
	scheduled_date_gmt DATETIME NOT NULL,
	type VARCHAR(50) NOT NULL,
	action LONGTEXT NOT NULL,
	log LONGTEXT NULL,
	unique_id VARCHAR(255) NULL,
	attempts INT NOT NULL DEFAULT 0,
	status VARCHAR(20) NOT NULL DEFAULT 'pending',
	last_attempt_gmt DATETIME NULL,
	available_at_gmt DATETIME NOT NULL,
	PRIMARY KEY  (id),
	KEY queue (queue),
	KEY status (status),
	KEY available_at_gmt (available_at_gmt)
) {$wpdb->get_charset_collate()};
SQL
			);

			if ( ! isset( $delta[ $wpdb->mantle_queue ] ) ) {
				throw new RuntimeException(
					sprintf(
						'Failed to create the %s table. Please check your database connection and permissions.',
						$wpdb->mantle_queue,
					),
				);
			}

			update_option( 'mantle_queue_db_version', self::DATABASE_VERSION );
		}
	}

	/**
	 * Delete database tables for the queue.
	 */
	protected function delete_tables(): void {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		if ( isset( $wpdb->mantle_queue ) ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->mantle_queue}" );
		}

		delete_option( 'mantle_queue_db_version' );
	}

	/**
	 * Recreate the database tables for the queue.
	 *
	 * This is useful for testing purposes to ensure a clean state.
	 */
	protected function recreate_tables(): void {
		$this->delete_tables();
		$this->create_tables();
	}
}
