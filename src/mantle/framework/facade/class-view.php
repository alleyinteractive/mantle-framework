<?php
/**
 * View Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Facade;

/**
 * View Facade
 */
class View extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'view';
	}
}
