<?php
/**
 * Http_Client class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

/**
 * Http Request Client
 *
 * @deprecated Use \Mantle\Http_Client\Factory instead.
 */
class Http_Client {
	/**
	 * Create an instance of the Http Client
	 */
	public static function create(): \Mantle\Http_Client\Factory {
		return new Factory();
	}
}
