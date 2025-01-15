<?php
/**
 * Cache Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Closure;

/**
 * Cache Facade
 *
 * @method static mixed|null get(string $key, mixed $default = null)
 * @method static iterable get_multiple(iterable $keys, mixed $default = null)
 * @method static mixed|null flexible(string $key, int|\DateInterval|\DateTimeInterface|null $stale, int|\DateInterval|\DateTimeInterface|null $expire, callable $callback)
 * @method static mixed|null pull(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value, null|int|\DateInterval $ttl = null)
 * @method static bool set_multiple(iterable $values, null|int|\DateInterval|\DateTimeInterface $ttl = null)
 * @method static int|bool increment(string $key, int $value = 1)
 * @method static int|bool decrement(string $key, int $value = 1)
 * @method static bool delete(string $key)
 * @method static bool delete_multiple(iterable $keys)
 * @method static bool clear()
 * @method static \Mantle\Cache\WordPress_Cache_Repository tags(string[]|string $names)
 * @method static mixed|null swr(string $key, int|\DateInterval|\DateTimeInterface|null $stale, int|\DateInterval|\DateTimeInterface|null $expire, Closure $callback)
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static bool setMultiple(iterable $values, null|int|\DateInterval $ttl = null)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool add(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool put(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool forget(string $key)
 * @method static bool has(string $key)
 * @method static bool forever(string $key, mixed $value)
 * @method static mixed|null remember(string $key, \DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static mixed|null remember_forever(string $key, \Closure $callback)
 * @method static mixed|null sear(string $key, \Closure $callback)
 *
 * @see \Mantle\Cache\WordPress_Cache_Repository
 */
class Cache extends Facade {
	/**
	 * Facade Accessor
	 */
	protected static function get_facade_accessor(): string {
		return 'cache';
	}
}
