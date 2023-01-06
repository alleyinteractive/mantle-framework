<?php
/**
 * Handler class file
 *
 * @package Mantle
 */

namespace Mantle\Featherkit\Exceptions;

use Mantle\Framework\Exceptions\Handler as Base_Handler;
use Mantle\Http\Response;
use Throwable;

/**
 * Featherkit Exception Handler
 */
class Handler extends Base_Handler {
	/**
	 * Report or log an exception.
	 *
	 * @param Throwable $e Exception thrown.
	 */
	public function report( Throwable $e ) {
		dump( static::class . '::' . __FUNCTION__ . '()', $e );
	}

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return bool
	 */
	public function should_report( Throwable $e ) {
		return true;
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param \Mantle\Http\Request $request
	 * @param \Throwable           $e Exception thrown.
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function render( $request, Throwable $e ) {
		return new Response();
	}
}
