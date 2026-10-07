<?php
/**
 * Database_Schema trait file
 *
 * phpcs:disable WordPress.DB
 *
 * @package Mantle
 */

namespace Mantle\Queue\Concerns;

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
	 * Register the queue table with `$wpdb`.
	 *
	 * Listing it in `$wpdb->tables` keeps `$wpdb->mantle_queue` pointed at the
	 * current site's table across `switch_to_blog()`, and lets WordPress drop it
	 * when a site is deleted.
	 */
	protected function register_table(): void {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		if ( ! in_array( 'mantle_queue', $wpdb->tables, true ) ) {
			$wpdb->tables[] = 'mantle_queue';
		}

		$wpdb->mantle_queue = $wpdb->prefix . 'mantle_queue'; // @phpstan-ignore-line property.notFound
	}

	/**
	 * Create the queue table for the current site if it is not installed.
	 */
	protected function create_tables(): void {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$this->register_table();

		if ( option( 'mantle_queue_db_version', 0 )->int() >= self::DATABASE_VERSION ) {
			return;
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		}

		$table = $wpdb->prefix . 'mantle_queue';

		// Indexed VARCHAR columns are kept short enough for the 767 byte index limit on older MySQL.
		dbDelta(
			<<<SQL
CREATE TABLE $table (
	id bigint unsigned NOT NULL AUTO_INCREMENT,
	queue VARCHAR(100) NOT NULL,
	created_date_gmt DATETIME NOT NULL,
	scheduled_date_gmt DATETIME NOT NULL,
	type VARCHAR(255) NOT NULL,
	action LONGTEXT NOT NULL,
	log LONGTEXT NULL,
	unique_id VARCHAR(191) NULL,
	attempts INT NOT NULL DEFAULT 0,
	status VARCHAR(20) NOT NULL DEFAULT 'pending',
	last_attempt_gmt DATETIME NULL,
	available_at_gmt DATETIME NOT NULL,
	PRIMARY KEY  (id),
	KEY queue_status_available (queue,status,available_at_gmt),
	KEY status_scheduled (status,scheduled_date_gmt)
) {$wpdb->get_charset_collate()};
SQL
		);

		// dbDelta() reports a table as created before running the query, so check that it exists.
		if ( ! $this->table_exists() ) {
			_doing_it_wrong( __METHOD__, esc_html( "Failed to create the {$table} table." ), '2.0.0' );

			return;
		}

		update_option( 'mantle_queue_db_version', self::DATABASE_VERSION );
	}

	/**
	 * Check if the queue table exists for the current site.
	 */
	protected function table_exists(): bool {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		// Query the table directly since SHOW TABLES does not list temporary tables used in tests.
		$suppress = $wpdb->suppress_errors();
		$exists   = false !== $wpdb->query( "SELECT 1 FROM {$wpdb->prefix}mantle_queue LIMIT 0" );

		$wpdb->suppress_errors( $suppress );

		return $exists;
	}

	/**
	 * Delete database tables for the queue.
	 */
	protected function delete_tables(): void {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mantle_queue" );

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
