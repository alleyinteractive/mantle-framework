<?php
/**
 * Kernel interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http;

use Mantle\Http\Request;

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
}
