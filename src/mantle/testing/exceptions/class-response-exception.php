<?php
/**
 * Response_Exception class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Exceptions;

/**
 * Exception thrown to represent a HTTP responses.
 */
abstract class Response_Exception extends Exception {
	/**
	 * Constructor.
	 *
	 * @param int         $status  The HTTP status code.
	 * @param string|null $message Optional exception message.
	 * @param int         $code    Optional exception code.
	 */
	public function __construct( public readonly int $status, ?string $message = null, int $code = 0 ) {
		parent::__construct( $message ?? "HTTP Response with status {$status}", $code );
	}
}
