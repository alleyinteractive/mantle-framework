<?php
/**
 * Kernel interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http;

use Mantle\Http\Request;
use Mantle\Http\Response;
/**
 * Http Kernel
 */
interface Kernel {
	/**
	 * Run the HTTP Application.
	 *
	 * @param Request $request Request object.
	 */
	public function handle( Request $request );

	/**
	 * Terminate the HTTP request.
	 *
	 * @param Request  $request  Request object.
	 * @param Response $response Response object.
	 * @return void
	 */
	public function terminate( Request $request, mixed $response ): void;
}
