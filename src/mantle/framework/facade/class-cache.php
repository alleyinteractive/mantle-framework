<?php
/**
 * Cache Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Facade;

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
