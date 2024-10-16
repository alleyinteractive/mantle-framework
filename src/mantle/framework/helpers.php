<?php
/**
 * Mantle Framework Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @deprecated Deprecated in favor of package-specific helpers.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 */

use Mantle\Contracts\Application;
use Mantle\Framework\Bootloader;
use Mantle\Framework\Exceptions\Handler as ExceptionHandler;

if ( ! function_exists( 'report' ) ) {
	/**
	 * Report an exception.
	 *
	 * @param  \Throwable|string  $exception
	 */
	function report( $exception ): void {
		if ( is_string( $exception ) ) {
			$exception = new Exception( $exception );
		}

		app( ExceptionHandler::class )->report( $exception );
	}
}

if ( ! function_exists( 'bootloader' ) ) {
	/**
	 * Retrieve the Bootloader instance.
	 *
	 * @param Application|Bootloader|null  $app Application instance, optional.
	 */
	function bootloader( Application|Bootloader|null $app = null ): Bootloader {
		// Handle legacy usage of the bootloader() function being passed an instance
		// of the bootloader from bootstrap/app.php. Can be removed in 2.0.
		if ( $app instanceof Bootloader ) {
			return $app;
		}

		return Bootloader::get_instance( $app );
	}
}
