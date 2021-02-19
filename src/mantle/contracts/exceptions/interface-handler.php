<?php
/**
 * Handler interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Exceptions;

use Throwable;

/**
 * Error Handler Contract
 */
interface Handler {
	/**
	 * Report or log an exception.
	 *
	 * @param Throwable $e Exception thrown.
	 *
	 * @throws \Exception Thrown on missing logger.
	 */
	public function report( Throwable $e );

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return bool
	 */
	public function should_report( Throwable $e );

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param \Mantle\Http\Request $request
	 * @param \Throwable           $e Exception thrown.
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @throws Throwable Thrown on error rendering.
	 */
	public function render( $request, Throwable $e );
}
