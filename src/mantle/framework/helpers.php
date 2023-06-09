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
	 * @return void
	 */
	function report( $exception ) {
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
	 * @param Application|null  $app Application instance, optional.
	 * @return Bootloader
	 */
	function bootloader( ?Application $app = null ): Bootloader {
		return Bootloader::get_instance( $app );
	}
}
