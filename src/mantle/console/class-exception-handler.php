<?php
/**
 * Exception_Handler class file
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Mantle\Contracts\Container;
use Mantle\Contracts\Exceptions\Handler as Exceptions_Handler;
use Throwable;

/**
 * Exception Handler for use in the console.
 *
 * Passes back the reporting to the application's exception handler and uses
 * Whoops and nunomaduro/collision to render the exception.
 */
class Exception_Handler implements Exceptions_Handler {
	/**
	 * Constructor.
	 *
	 * @param Container          $container Container instance.
	 * @param Exceptions_Handler $app_handler Application exception handler.
	 */
	public function __construct(
		public Container $container,
		public Exceptions_Handler $app_handler,
	) {
	}

	/**
	 * Report or log an exception.
	 *
	 * @param Throwable $e Exception thrown.
	 *
	 * @throws \Exception Thrown on missing logger.
	 */
	public function report( Throwable $e ) {
		return $this->app_handler->report( $e );
	}

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return bool
	 */
	public function should_report( Throwable $e ) {
		return $this->app_handler->should_report( $e );
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param \Mantle\Http\Request $request
	 * @param \Throwable           $e Exception thrown.
	 * @return void
	 *
	 * @throws Throwable Thrown on error rendering.
	 */
	public function render( $request, Throwable $e ) {
		( new \NunoMaduro\Collision\Provider() )->register();

		throw $e;
	}
}
