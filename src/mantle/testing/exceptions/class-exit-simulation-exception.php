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
 * Thrown to simulate an exit() call during testing.
 */
class Exit_Simulation_Exception extends Response_Exception {
	/**
	 * Constructor.
	 *
	 * @param int $status  The exit status code.
	 */
	public function __construct( readonly public int $status = 200 ) {
		parent::__construct( 'Simulated exit with status ' . $status, $status );
	}
}
