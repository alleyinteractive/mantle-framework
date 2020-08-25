<?php
/**
 * Event Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Facade;

/**
 * Event Facade
 *
 * @method static \Closure create_class_listener(string $listener, bool $wildcard = false)
 * @method static \Closure make_istener(\Closure|string $listener, bool $wildcard = false)
 * @method static array get_listeners(string $eventName)
 * @method static array|null dispatch(string|object $event, mixed $payload = [], bool $halt = false)
 * @method static array|null until(string|object $event, mixed $payload = [])
 * @method static bool has_listeners(string $eventName)
 * @method static void flush(string $event)
 * @method static void forget(string $event)
 * @method static void forget_pushed()
 * @method static void listen(string|array $events, \Closure|string $listener)
 * @method static void push(string $event, array $payload = [])
 * @method static void subscribe(object|string $subscriber)
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
