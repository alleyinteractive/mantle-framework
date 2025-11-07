<?php
/**
 * Exit_Simulation_Exception class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Exceptions;

/**
 * Exit Simulation Exception
 *
 * Thrown to simulate an exit() call during testing. For example, when using a
 * custom rewrite rule that would normally call exit() to terminate execution
 * (before WordPress proceeds on with its normal flow) this exception can be
 * thrown instead to allow tests to continue running.
 */
class Exit_Simulation_Exception extends Response_Exception {
	/**
	 * Constructor.
	 *
	 * @param int                  $exit_status The exit status code. 0 indicates a normal exit.
	 * @param int|null             $response_code The HTTP response code.
	 * @param array<string,string> $headers The HTTP headers.
	 * @param string|null          $message Optional exception message.
	 */
	public function __construct( public readonly int $exit_status = 0, public readonly ?int $response_code = null, array $headers = [], ?string $message = null ) {
		// If no response code is provided, use the current HTTP response code.
		if ( null === $response_code ) {
			$response_code = http_response_code();
		}

		parent::__construct(
			status: is_int( $response_code ) ? $response_code : 200,
			headers: $headers,
			message: $message ?? "Simulated exit with status {$exit_status}",
		);
	}
}
