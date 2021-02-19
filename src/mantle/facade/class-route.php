<?php
/**
 * Route Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Route Facade
 */
class Route extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'router';
	}
}
