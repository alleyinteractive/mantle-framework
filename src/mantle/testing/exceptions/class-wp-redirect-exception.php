<?php
/**
 * This file contains the WP_Redirect_Exception class
 *
 * @package Mantle
 */

namespace Mantle\Testing\Exceptions;

/**
 * Exception thrown when a redirect is encountered.
 */
class WP_Redirect_Exception extends Exception {
	/**
	 * Constructor.
	 *
	 * @param int    $status  The HTTP status code.
	 * @param string $location The location to redirect to.
	 */
	public function __construct( public readonly int $status, public readonly string $location ) {
		parent::__construct( sprintf( 'Redirect to %s with status %d', $location, $status ), $status );
	}
}
