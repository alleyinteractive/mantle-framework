<?php
/**
 * This file contains the Utils class
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing;

/**
 * Assorted testing utilities.
 *
 * A fork of https://github.com/WordPress/wordpress-develop/blob/master/tests/phpunit/includes/utils.php.
 */
class Utils {
	/**
	 * Get the output from a given callable.
	 *
	 * @param callable $callable Callable to execute.
	 * @param array    $args     Arguments to pass to the callable.
	 * @return false|string Rendered output on success, false on failure.
	 */
	public static function get_echo( $callable, $args = [] ) {
		ob_start();
		call_user_func_array( $callable, $args );
		return ob_get_clean();
	}
	/**
	 * Unregister a post status.
	 *
	 * @param string $status Post status to unregister.
	 */
	public static function unregister_post_status( $status ) {
		unset( $GLOBALS['wp_post_statuses'][ $status ] );
	}

	/**
	 * Remove WP query vars from the global space.
	 */
	public static function cleanup_query_vars() {
		// Clean out globals to stop them polluting wp and wp_query.
		foreach ( $GLOBALS['wp']->public_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		foreach ( $GLOBALS['wp']->private_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		foreach ( get_taxonomies( [], 'objects' ) as $t ) {
			if ( $t->publicly_queryable && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}

		foreach ( get_post_types( [], 'objects' ) as $t ) {
			if ( is_post_type_viewable( $t ) && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}
	}
}
