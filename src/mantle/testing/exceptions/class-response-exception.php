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
	 * @param int                  $status  The HTTP status code.
	 * @param array<string,string> $headers The HTTP headers.
	 * @param string|null          $message Optional exception message.
	 * @param int                  $code    Optional exception code.
	 */
	public function __construct( public readonly int $status, public array $headers = [], ?string $message = null, int $code = 0 ) {
		// Ensure headers are all keyed by lowercase.
		$this->headers = array_change_key_case( $this->headers, CASE_LOWER );

		parent::__construct( $message ?? "HTTP Response with status {$status}", $code );
	}
}
