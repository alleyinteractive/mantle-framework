<?php
/**
 * Event Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Event
 *
 * @method static void listen(string|array $events, mixed $listener, int $priority = 10)
 * @method static bool has_listeners(string $event_name)
 * @method static void subscribe(object|string $subscriber)
 * @method static mixed dispatch(string|object $event, mixed $payload = [null])
 * @method static array get_listeners(string $event_name)
 * @method static \Closure make_listener(\Closure|string $listener)
 * @method static \Closure create_class_listener(string $listener)
 * @method static void forget(string|object $event, callable|string $listener = null, int $priority = 10)
 * @method static void action(string $action, callable $callback, int $priority = 10)
 * @method static void action_if(callable|bool $condition, string $action, callable $callback, int $priority = 10)
 * @method static void filter(string $action, callable $callback, int $priority = 10)
 *
 * @see \Mantle\Events\Dispatcher
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
