<?php
/**
 * This file contains assorted helpers for responses
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Support\Helpers;

use Mantle\Testing\Exceptions\Exit_Simulation_Exception;

/**
 * Terminate the request, simulating an exit call.
 *
 * If running in a unit testing environment, an
 * `Exit_Simulation_Exception` will be thrown instead of calling `exit()`.
 *
 * @param int         $exit_status The exit status code. 0 indicates a normal exit.
 * @param int|null    $response_code The HTTP response code, optional. If not provided, will default to 200.
 *
 * @throws \RuntimeException If the `Exit_Simulation_Exception` class is not found during unit testing.
 * @throws \Mantle\Testing\Exceptions\Exit_Simulation_Exception Thrown when in a unit testing environment.
 */
function terminate_request( int $exit_status = 0, ?int $response_code = 200 ): never {
	if ( is_unit_testing() ) {
		if ( ! class_exists( Exit_Simulation_Exception::class ) ) {
			throw new \RuntimeException( Exit_Simulation_Exception::class . ' not found. Please ensure that mantle-framework/testing is installed.' );
		}

		throw new Exit_Simulation_Exception( $exit_status, $response_code );
	}

	if ( null !== $response_code ) {
		status_header( $response_code );
	}

	exit( $exit_status ); // phpcs:ignore WordPress
}
