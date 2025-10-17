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
	 * @param int         $status The exit status code.
	 * @param string|null $message Optional exception message.
	 */
	public function __construct( int $status = 200, ?string $message = null ) {
		parent::__construct( $status, $message ?? "Simulated exit with status {$status}" );
	}
}
