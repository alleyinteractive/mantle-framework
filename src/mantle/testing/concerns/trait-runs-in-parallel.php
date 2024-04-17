<?php

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Utils;
use wpdb;

trait Runs_In_Parallel {
	/**
	 * Set up the trait to install WordPress in the parallel environment.
	 */
	protected static function runs_in_parallel_set_up_before_class(): void {
		global $table_prefix, $wpdb;

		// If we're not running in parallel, we don't need to do anything.
		if ( empty( getenv('TEST_TOKEN') ) ) {
			return;
		}

		// Set the table prefix to a unique value for this test run.
		$table_prefix = 'wptests_parallel_' . getenv('TEST_TOKEN') . '_';

		// Setup the wpdb global.
		wp_set_wpdb_vars();
		// // Update $wpdb with the new table prefix.
		// if ( $wpdb instanceof wpdb ) {
		// 	$wpdb->set_prefix( $table_prefix );
		// }

		ray("prefix: $table_prefix installing wordpress");

		\Mantle\Testing\manager()->install();
	}
}
