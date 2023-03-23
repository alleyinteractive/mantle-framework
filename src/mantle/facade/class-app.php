<?php
/**
 * App Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * App Facade
 */
class App extends Facade {
	/**
	 * Facade Accessor
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'app';
	}
}
