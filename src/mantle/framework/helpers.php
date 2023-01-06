<?php
/**
 * Mantle Framework Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */

use Mantle\Framework\Exceptions\Handler as ExceptionHandler;

if ( ! function_exists( 'report' ) ) {
	/**
	 * Report an exception.
	 *
	 * @param \Throwable|string $exception Exception/message to report.
	 * @return void
	 */
	function report( $exception ) {
		if ( is_string( $exception ) ) {
			$exception = new Exception( $exception );
		}

		app( ExceptionHandler::class )->report( $exception );
	}
}
