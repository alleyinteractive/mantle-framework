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
 * @method static \Mantle\Scheduling\Event call(callable $callback, array $arguments = [])
 * @method static \Mantle\Scheduling\Event command(string $command, array $arguments = [], array $assoc_args = [])
 * @method static \Mantle\Scheduling\Event job(string $job, array $arguments = [])
 *
 * @see \Mantle\Scheduling\Schedule
 */
class Schedule extends Facade {
	/**
	 * Get the registered name of the component.
	 */
	protected static function get_facade_accessor(): string {
		return 'scheduler';
	}
}
