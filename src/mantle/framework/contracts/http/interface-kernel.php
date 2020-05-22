<?php
/**
 * Kernel interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Http;

use Symfony\Component\HttpFoundation\Request;

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
