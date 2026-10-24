<?php
/**
 * Higher_Order_Proxy_Method class file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\PHPStan;

/**
 * Stand-in for any method called on a higher order proxy.
 *
 * PHPStan reflects this method for every call made through the proxy.
 */
class Higher_Order_Proxy_Method {
	/**
	 * Forwarded method call.
	 *
	 * @param mixed ...$arguments Arguments passed to the method.
	 */
	public function call( mixed ...$arguments ): mixed {
		return $arguments;
	}
}
