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

use Mantle\Application\Application;
use Mantle\Contracts\Http\Routing\Response_Factory;
use Mantle\Contracts\Http\View\Factory as View_Factory;
use Mantle\Support\Environment;
use Mantle\Framework\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Routing\Generator\UrlGenerator;

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
