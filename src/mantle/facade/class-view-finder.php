<?php
/**
 * View_Loader Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * View Finder Facade
 */
class View_Finder extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'view.loader';
	}
}
