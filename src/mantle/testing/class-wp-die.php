<?php
/**
 * This file contains the WP_Die helper class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Testing\Exceptions\WP_Die_Exception;

/**
 * WP_Die helpers.
 */
class WP_Die {

	/**
	 * Retrieves the `wp_die()` handler.
	 *
	 * @return callable The test die handler.
	 */
	public static function get_handler() {
		return [ static::class, 'handler' ];
	}

	/**
	 * Returns the die handler.
	 *
	 * @return callable The die handler.
	 */
	public static function get_toggled_handler() {
		return [ static::class, 'toggled_handler' ];
	}

	/**
	 * Returns the die handler.
	 *
	 * @return callable The die handler.
	 */
	public static function get_exit_handler() {
		return [ static::class, 'exit_handler' ];
	}

	/**
	 * Handles the WP die handler by outputting the given values as text.
	 *
	 * @param string $message The message.
	 * @param string $title   The title.
	 * @param array  $args    Array with arguments.
	 */
	public static function toggled_handler( $message, $title = '', $args = [] ) {
		if ( ! $GLOBALS['_wp_die_disabled'] ) {
			static::txt_handler( $message, $title, $args );
		}
	}

	/**
	 * Throws an exception when called.
	 *
	 * @throws WP_Die_Exception Exception containing the message.
	 *
	 * @param string $message The `wp_die()` message.
	 */
	public static function handler( $message ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		if ( ! is_scalar( $message ) ) {
			$message = '0';
		}

		throw new WP_Die_Exception( $message );
	}

	/**
	 * Dies without an exit.
	 *
	 * @param string|\WP_Error $message The message.
	 * @param string $title   The title.
	 * @param array  $args    Array with arguments.
	 */
	public static function txt_handler( $message, $title, $args ) {
		// phpcs:disable WordPress.Security.EscapeOutput

		// Display fatal error information from wp_die().
		if ( is_wp_error( $message ) && 'internal_server_error' === $message->get_error_code() ) {
			$error = $message->get_error_data( 'internal_server_error' )['error'] ?? [];

			if ( ! empty( $error ) ) {

				echo "\nwp_die called\n";
				echo "Internal server error: {$error['message']}\n\n";

				if ( ! empty( $error['file'] ) ) {
					echo "File: {$error['file']}\n";
				}

				if ( ! empty( $error['line'] ) ) {
					echo "Line: {$error['line']}\n";
				}

				return;
			}
		}

		[ $message, $title, $args ] = _wp_die_process_input( $message, $title, $args );

		echo "\nwp_die called\n";
		echo "Message : $message\n";
		echo "Title : $title\n";
		if ( ! empty( $args ) ) {
			echo "Args: \n";
			foreach ( $args as $k => $v ) {
				echo "\t $k : $v\n";
			}
		}

		// phpcs:enable
	}

	/**
	 * Dies with an exit.
	 *
	 * @param string $message The message.
	 * @param string $title   The title.
	 * @param array  $args    Array with arguments.
	 */
	public static function exit_handler( $message, $title, $args ) {
		// phpcs:disable WordPress.Security.EscapeOutput
		echo "\nwp_die called\n";
		echo "Message : $message\n";
		echo "Title : $title\n";
		if ( ! empty( $args ) ) {
			echo "Args: \n";
			foreach ( $args as $k => $v ) {
				echo "\t $k : $v\n";
			}
		}
		exit( 1 );
		// phpcs:enable
	}

	/**
	 * Disables the WP die handler.
	 */
	public static function disable() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$GLOBALS['_wp_die_disabled'] = true;
	}

	/**
	 * Enables the WP die handler.
	 */
	public static function enable() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$GLOBALS['_wp_die_disabled'] = false;
	}
}
