<?php
/**
 * Core_Shim trait file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

/**
 * Core Shims
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait Core_Shim {
	/**
	 * Fake 'set_up' method to allow for easier transition and some PHPUnit 8
	 * compatibility.
	 *
	 * @return void
	 */
	public function set_up() {
		// Do nothing.
	}

	/**
	 * Fake 'tear_down' method to allow for easier transition and some PHPUnit 8
	 * compatibility.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Do nothing.
	}

	/**
	 * Allows tests to be skipped when Multisite is not in use.
	 *
	 * Use in conjunction with the ms-required group.
	 */
	public function skipWithoutMultisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Test only runs on Multisite' );
		}
	}

	/**
	 * Allows tests to be skipped when Multisite is in use.
	 *
	 * Use in conjunction with the ms-excluded group.
	 */
	public function skipWithMultisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Test does not run on Multisite' );
		}
	}

	/**
	 * Allows tests to be skipped if the HTTP request times out.
	 *
	 * @deprecated Not encouraged for use in Mantle. Remote requests should be mocked.
	 *
	 * @param array|WP_Error $response HTTP response.
	 */
	public function skipTestOnTimeout( $response ) {
		if ( ! is_wp_error( $response ) ) {
			return;
		}
		if ( 'connect() timed out!' === $response->get_error_message() ) {
			$this->markTestSkipped( 'HTTP timeout' );
		}

		if ( false !== strpos( $response->get_error_message(), 'timed out after' ) ) {
			$this->markTestSkipped( 'HTTP timeout' );
		}

		if ( 0 === strpos( $response->get_error_message(), 'stream_socket_client(): unable to connect to tcp://s.w.org:80' ) ) {
			$this->markTestSkipped( 'HTTP timeout' );
		}
	}
}
