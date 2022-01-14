<?php
/**
 * Event Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Event Facade
 *
 * @method static void listen(string|array $events, \Closure|string $listener)
 * @method static mixed dispatch(string|object $event, array $payload = [null])
 * @method static bool has_listeners(string $event_name)
 * @method static void action(string $action, callable $callback)
 * @method static void filter(string $filter, callable $callback)
 */
class Event extends Facade {
	/**
	 * Facade Accessor
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'events';
	}
}
