<?php
/**
 * Http Facade class file
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Mantle\Http\Client\Http_Client;

/**
 * Http Facade
 *
 * @see \Mantle\Http\Client\Http_Client
 * @mixin \Mantle\Http\Client\Http_Client
 */
class Http extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return Http_Client::class;
	}
}
