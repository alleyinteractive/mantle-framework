<?php
/**
 * This file contains the WP_Redirect_Exception class
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Exceptions;

/**
 * Exception thrown when a redirect is encountered.
 */
class WP_Redirect_Exception extends Response_Exception {
	/**
	 * Constructor.
	 *
	 * @param int                  $status  The HTTP status code.
	 * @param string               $location The location to redirect to.
	 * @param array<string,string> $headers The HTTP headers.
	 */
	public function __construct( int $status, public readonly string $location, array $headers = [] ) {
		// Ensure headers are all keyed by lowercase.
		$headers = array_change_key_case( $headers, CASE_LOWER );

		if ( ! isset( $headers['location'] ) ) {
			$headers['location'] = $location;
		}

		parent::__construct( $status, $headers, "Redirect to {$location} with status {$status}" );
	}
}
