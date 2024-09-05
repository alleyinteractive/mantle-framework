<?php
/**
 * Repository class file.
 *
 * @package Mantle
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Cache;

use Closure;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache repository.
 *
 * This class contains some camelCase methods to match the PSR interface.
 */
abstract class Repository implements CacheInterface {
	/**
	 * Retrieve a value from cache.
	 *
	 * @template TCacheValue
	 *
	 * @param string                                $key Cache key.
	 * @param TCacheValue|(\Closure(): TCacheValue) $default Default value.
	 * @return (TCacheValue is null ? mixed : TCacheValue)
	 */
	abstract public function get( string $key, mixed $default = null ): mixed;

	/**
	 * Retrieve multiple cache keys.
	 *
	 * @param iterable $keys Cache keys.
	 * @param mixed    $default Default value.
	 */
	abstract public function get_multiple( iterable $keys, mixed $default = null ): iterable;

	/**
	 * Retrieve a value from cache. Return it if it exists and if stale, refresh it after the response is sent.
	 *
	 * @template TCacheValue
	 *
	 * @param string                                    $key
	 * @param int|\DateInterval|\DateTimeInterface|null $stale
	 * @param int|\DateInterval|\DateTimeInterface|null $expire
	 * @param Closure(): TCacheValue                    $callback
	 * @return (TCacheValue is null ? mixed : TCacheValue)
	 */
	abstract public function flexible( string $key, int|\DateInterval|\DateTimeInterface|null $stale, int|\DateInterval|\DateTimeInterface|null $expire, callable $callback ): mixed;

	/**
	 * Set a cache item.
	 *
	 * @param string                 $key Cache key.
	 * @param mixed                  $value Item value.
	 * @param null|int|\DateInterval $ttl TTL.
	 */
	abstract public function set( string $key, mixed $value, int|\DateInterval|null $ttl = null ): bool;

	/**
	 * Set multiple keys.
	 *
	 * @param iterable               $values Key value pair of values to set.
	 * @param null|int|\DateInterval $ttl Cache TTL.
	 */
	abstract public function set_multiple( iterable $values, null|int|\DateInterval $ttl = null ): bool;

	/**
	 * Delete a cache key.
	 *
	 * @param string $key Cache key.
	 */
	abstract public function delete( string $key ): bool;

	/**
	 * Delete multiple cache keys.
	 *
	 * @param iterable<string> $keys Cache keys.
	 */
	abstract public function delete_multiple( iterable $keys ): bool;

	/**
	 * Alias for flexible.
	 *
	 * @template TCacheValue
	 *
	 * @param string                                    $key
	 * @param int|\DateInterval|\DateTimeInterface|null $stale
	 * @param int|\DateInterval|\DateTimeInterface|null $expire
	 * @param Closure(): TCacheValue                    $callback
	 * @return (TCacheValue is null ? mixed : TCacheValue)
	 */
	public function swr( string $key, int|\DateInterval|\DateTimeInterface|null $stale, int|\DateInterval|\DateTimeInterface|null $expire, Closure $callback ): mixed {
		return $this->flexible( $key, $stale, $expire, $callback );
	}

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 */
	public function pull( string $key, mixed $default = null ): mixed {
		$value = $this->get( $key );

		if ( ! is_null( $value ) ) {
			$value = $default;

			$this->forget( $key );
		}

		return $value;
	}

	/**
	 * Alias for get_multiple for the PSR interface.
	 *
	 * @param iterable $keys Cache keys.
	 * @param mixed    $default Default value.
	 */
	public function getMultiple( iterable $keys, mixed $default = null ): iterable {
		return $this->get_multiple( $keys, $default );
	}

	/**
	 * Alias for set_multiple for the PSR interface.
	 *
	 * @param iterable               $values Key value pair of values to set.
	 * @param null|int|\DateInterval $ttl Cache TTL.
	 */
	public function setMultiple( iterable $values, null|int|\DateInterval $ttl = null ): bool {
		return $this->set_multiple( $values, $ttl );
	}

	/**
	 * Alias for delete_multiple for the PSR interface.
	 *
	 * @param iterable<string> $keys Cache keys.
	 */
	public function deleteMultiple( iterable $keys ): bool {
		return $this->delete_multiple( $keys );
	}

	/**
	 * Store an item in the cache if the key does not exist.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 */
	public function add( string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null ): bool {
		if ( is_null( $this->get( $key ) ) ) {
			return $this->put( $key, $value, $ttl );
		}

		return false;
	}

	/**
	 * Alias for set.
	 *
	 * @param string                                    $key Cache key.
	 * @param mixed                                     $value Item value.
	 * @param \DateTimeInterface|\DateInterval|int|null $ttl Cache TTL.
	 */
	public function put( string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null ): bool {
		return $this->set( $key, $value, $ttl );
	}

	/**
	 * Alias for delete.
	 *
	 * @param string $key Cache key.
	 */
	public function forget( string $key ): bool {
		return $this->delete( $key );
	}

	/**
	 * Check if a cache item exists in the store.
	 *
	 * @param string $key Cache key.
	 */
	public function has( string $key ): bool {
		return '__default__' !== $this->get( $key, '__default__' );
	}

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string $key Cache key.
	 * @param  mixed  $value Value.
	 */
	public function forever( string $key, mixed $value ): bool {
		return $this->put( $key, $value, null );
	}

	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 *
	 * @param  string                                    $key
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 * @param  \Closure                                  $callback
	 */
	public function remember( string $key, \DateTimeInterface|\DateInterval|int|null $ttl, Closure $callback ): mixed {
		$value = $this->get( $key );

		if ( ! is_null( $value ) ) {
			return $value;
		}

		return $this->put( $key, $callback(), $ttl );
	}

	/**
	 * Get an item from the cache, or execute the given Closure and store the result forever.
	 *
	 * @param  string   $key Cache key.
	 * @param  \Closure $callback Callback to invoke.
	 */
	public function remember_forever( string $key, Closure $callback ): mixed {
		$value = $this->get( $key );

		if ( ! is_null( $value ) ) {
			return $value;
		}

		return $this->put( $key, $callback(), null );
	}

	/**
	 * Get an item from the cache, or execute the given Closure and store the result forever.
	 *
	 * @param  string   $key Cache key.
	 * @param  \Closure $callback Callback to invoke.
	 */
	public function sear( string $key, Closure $callback ): mixed {
		return $this->remember_forever( $key, $callback );
	}
}
