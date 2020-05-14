<?php
/**
 * This file contains the Refresh_Database trait
 *
 * @package Mantle
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users

namespace Mantle\Framework\Testing\Concerns;

trait Refresh_Database {

	/**
	 * Routines to run before setupBeforeClass.
	 */
	public static function refresh_database_pre_setup_before_class() {
		global $wpdb;

		$wpdb->suppress_errors = false;
		$wpdb->show_errors     = true;
		$wpdb->db_connect();

		// phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted
		ini_set( 'display_errors', 1 );
	}

	/**
	 * Routines to run on tearDown().
	 */
	public function refresh_database_tear_down() {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		remove_filter( 'query', [ $this, 'create_temporary_tables' ] );
		remove_filter( 'query', [ $this, 'drop_temporary_tables' ] );
	}

	/**
	 * Commit the queries in a transaction.
	 */
	public static function commit_transaction() {
		global $wpdb;
		$wpdb->query( 'COMMIT;' );
	}

	/**
	 * Deletes all data from the database.
	 */
	public static function delete_all_data() {
		global $wpdb;

		foreach ( [
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->comments,
			$wpdb->commentmeta,
			$wpdb->term_relationships,
			$wpdb->termmeta,
		] as $table ) {
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM {$table}" );
		}

		foreach ( [
			$wpdb->terms,
			$wpdb->term_taxonomy,
		] as $table ) {
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM {$table} WHERE term_id != 1" );
		}

		$wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET count = 0" );

		$wpdb->query( "DELETE FROM {$wpdb->users} WHERE ID != 1" );
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id != 1" );
	}

	/**
	 * Starts a database transaction.
	 */
	public function start_transaction() {
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'START TRANSACTION;' );
		add_filter( 'query', [ $this, 'create_temporary_tables' ] );
		add_filter( 'query', [ $this, 'drop_temporary_tables' ] );
	}

	/**
	 * Replaces the `CREATE TABLE` statement with a `CREATE TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 * @return string The altered query.
	 */
	public function create_temporary_tables( $query ) {
		if ( 0 === strpos( trim( $query ), 'CREATE TABLE' ) ) {
			return substr_replace( trim( $query ), 'CREATE TEMPORARY TABLE', 0, 12 );
		}
		return $query;
	}

	/**
	 * Replaces the `DROP TABLE` statement with a `DROP TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 * @return string The altered query.
	 */
	public function drop_temporary_tables( $query ) {
		if ( 0 === strpos( trim( $query ), 'DROP TABLE' ) ) {
			return substr_replace( trim( $query ), 'DROP TEMPORARY TABLE', 0, 10 );
		}
		return $query;
	}
}
// phpcs:enable
