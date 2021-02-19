<?php
/**
 * Cache Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Cache Facade
 */
class Cache extends Facade {
	/**
	 * Facade Accessor
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'cache';
	}
}
