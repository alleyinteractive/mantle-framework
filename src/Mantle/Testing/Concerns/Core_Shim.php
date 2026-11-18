<?php
/**
 * Core_Shim trait file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use WP_Error;

/**
 * Core Shims
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait Core_Shim {
	use Refresh_Database;

	/**
	 * Fake 'set_up' method to allow for easier transition and some PHPUnit 8
	 * compatibility.
	 */
	public function set_up() {
		// Do nothing.
	}

	/**
	 * Fake 'tear_down' method to allow for easier transition and some PHPUnit 8
	 * compatibility.
	 */
	public function tear_down() {
		// Do nothing.
	}

	/**
	 * Allows tests to be skipped when Multisite is not in use.
	 *
	 * Use in conjunction with the ms-required group.
	 *
	 * @param string $message Optional. Message to display when the test is skipped.
	 */
	public function skipWithoutMultisite( string $message = 'Test only runs on Multisite' ): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( $message );
		}
	}

	/**
	 * Allows tests to be skipped when Multisite is in use.
	 *
	 * Use in conjunction with the ms-excluded group.
	 *
	 * @param string $message Optional. Message to display when the test is skipped.
	 */
	public function skipWithMultisite( string $message = 'Test does not run on Multisite' ): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( $message );
		}
	}

	/**
	 * Allows tests to be skipped if the HTTP request times out.
	 *
	 * @deprecated Not encouraged for use in Mantle. Remote requests should be mocked.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @param string         $message  Optional. Message to display when the test is skipped.
	 */
	#[\Deprecated( 'Not encouraged for use in Mantle. Remote requests should be mocked.' )]
	public function skipTestOnTimeout( $response, string $message = 'HTTP timeout' ): void {
		if ( ! is_wp_error( $response ) ) {
			return;
		}

		if ( 'connect() timed out!' === $response->get_error_message() ) {
			$this->markTestSkipped( $message );
		}

		if ( false !== strpos( $response->get_error_message(), 'timed out after' ) ) {
			$this->markTestSkipped( $message );
		}

		if ( str_starts_with( $response->get_error_message(), 'stream_socket_client(): unable to connect to tcp://s.w.org:80' ) ) {
			$this->markTestSkipped( $message );
		}
	}
}
