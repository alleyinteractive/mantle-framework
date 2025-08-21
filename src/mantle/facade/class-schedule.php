<?php
/**
 * View_Loader Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Schedule Facade
 *
 * @see \Mantle\Scheduling\Schedule
 */
class Schedule extends Facade {
	/**
	 * Get the registered name of the component.
	 */
	protected static function get_facade_accessor(): string {
		return 'schedule';
	}
}
