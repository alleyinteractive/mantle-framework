<?php
/**
 * Request Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Facade;

/**
 * Request Facade
 *
 * @see \Mantle\Framework\Http\Request
 */
class Request extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'request';
	}
}
