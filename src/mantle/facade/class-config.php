<?php
/**
 * Config Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Config Facade
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
