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

use function Mantle\Support\Helpers\collect;

/**
 * Cache repository.
 *
 * This class contains some camelCase methods to match the PSR interface.
 */
abstract class Repository {
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
	 * Remove an item from the cache.
	 *
	 * @param  string $key Cache key.
	 */
	abstract public function forget( $key ): bool;

	/**
	 * Store an item in the cache.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 */
	abstract public function put( $key, $value, $ttl = null ): bool;

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function pull( $key, $default = null ) {
		$value = $this->get( $key );

		if ( ! is_null( $value ) ) {
			$value = $default;

			$this->forget( $key );
		}

		return $value;
	}

	/**
	 * Store an item in the cache if the key does not exist.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 * @return bool
	 */
	public function add( $key, $value, $ttl = null ) {
		if ( is_null( $this->get( $key ) ) ) {
			return $this->put( $key, $value, $ttl );
		}

		return false;
	}

	/**
	 * Set a cache item.
	 *
	 * @param string                 $key Cache key.
	 * @param mixed                  $value Item value.
	 * @param null|int|\DateInterval $ttl TTL.
	 */
	public function set( string $key, mixed $value, null|int|\DateInterval $ttl = null ): bool {
		return $this->put( $key, $value, $ttl );
	}

	/**
	 * Delete a cache key.
	 *
	 * @param string $key Cache key.
	 */
	public function delete( string $key ): bool {
		return $this->forget( $key );
	}

	/**
	 * Retrieve multiple cache keys.
	 *
	 * @param iterable $keys Cache keys.
	 * @param mixed    $default Default value.
	 */
	public function getMultiple( iterable $keys, mixed $default = null ): iterable {
		return collect( $keys )
			->map(
				fn( $key) => $this->pull( $key, $default )
			)
			->to_array();
	}

	/**
	 * Set multiple keys.
	 *
	 * @param iterable               $values Key value pair of values to set.
	 * @param null|int|\DateInterval $ttl Cache TTL.
	 */
	public function setMultiple( iterable $values, null|int|\DateInterval $ttl = null ): bool {
		foreach ( $values as $key => $value ) {
			$this->set( $key, $value, $ttl );
		}

		return true;
	}

	/**
	 * Delete multiple cache keys.
	 *
	 * @param iterable<string> $keys Cache keys.
	 */
	public function deleteMultiple( iterable $keys ): bool {
		collect( $keys )->each(
			fn( $key) => $this->delete( $key )
		);

		return true;
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
	 * @return bool
	 */
	public function forever( $key, $value ) {
		return $this->put( $key, $value, null );
	}

	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 *
	 * @param  string                                    $key
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 * @param  \Closure                                  $callback
	 * @return mixed
	 */
	public function remember( $key, $ttl, Closure $callback ) {
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
	 * @return mixed
	 */
	public function sear( $key, Closure $callback ) {
		return $this->rememberForever( $key, $callback );
	}

	/**
	 * Get an item from the cache, or execute the given Closure and store the result forever.
	 *
	 * @param  string   $key Cache key.
	 * @param  \Closure $callback Callback to invoke.
	 * @return mixed
	 */
	public function rememberForever( $key, Closure $callback ) {
		$value = $this->get( $key );

		if ( ! is_null( $value ) ) {
			return $value;
		}

		return $this->put( $key, $callback(), null );
	}
}
