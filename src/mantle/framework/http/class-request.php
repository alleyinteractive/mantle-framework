<?php
/**
 * Request class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

/**
 * Request Object
 */
class Request extends HttpFoundationRequest {
	/**
	 * Create a request object.
	 *
	 * @return static
	 */
	public static function capture() {
		return static::createFromGlobals();
	}
}
