<?php
/**
 * Cache Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Cache Facade
 *
 * @method static mixed store(string $name = null)
 * @method static \Mantle\Cache\Cache_Manager extend(string $name, Closure $callback)
 *
 * @see \Mantle\Cache\Cache_Manager
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
