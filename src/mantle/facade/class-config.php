<?php
/**
 * Config Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * @method static bool has(string $key)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(array|string $key, mixed $value)
 * @method static array all()
 *
 * @see \Mantle\Config\Repository
 */
class Config extends Facade {
	/**
	 * Facade Accessor
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'config';
	}
}
