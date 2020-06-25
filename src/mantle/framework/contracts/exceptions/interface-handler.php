<?php

namespace Mantle\Framework\Contracts\Exceptions;

use Throwable;

interface Handler {

	/**
	 * Report or log an exception.
	 *
	 * @param  \Throwable $e
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function report( Throwable $e);

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param  \Throwable $e
	 * @return bool
	 */
	public function should_report( Throwable $e);

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Throwable               $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @throws \Throwable
	 */
	public function render( $request, Throwable $e);

	/**
	 * Render an exception to the console.
	 *
	 * @param  \Symfony\Component\Console\Output\OutputInterface $output
	 * @param  \Throwable                                        $e
	 * @return void
	 */
	public function render_for_console( $output, Throwable $e);
}
