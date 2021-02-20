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

use function Mantle\Framework\Helpers\collect;

/**
 * Cache repository.
 */
abstract class Repository {
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
	 * @param string $key Cache key.
	 * @param mixed  $value Item value.
	 * @param int    $ttl TTL.
	 * @return mixed
	 */
	public function set( $key, $value, $ttl = null ) {
		return $this->put( $key, $value, $ttl );
	}

	/**
	 * Delete a cache key.
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 */
	public function delete( $key ) {
		return $this->forget( $key );
	}

	/**
	 * Retrieve multiple cache keys.
	 *
	 * @param string[] $keys Cache keys.
	 * @param mixed    $default Default value.
	 * @return mixed
	 */
	public function getMultiple( $keys, $default = null ) {
		return collect( $keys )
			->map(
				function( $key ) use ( $default ) {
					return $this->pull( $key, $default );
				}
			)
			->to_array();
	}

	/**
	 * Set multiple keys.
	 *
	 * @param array $values Key value pair of values to set.
	 * @param int   $ttl Cache TTL.
	 * @return bool
	 */
	public function setMultiple( $values, $ttl = null ) {
		foreach ( $values as $key => $value ) {
			$this->set( $key, $value, $ttl );
		}

		return true;
	}

	/**
	 * Delete multiple cache keys.
	 *
	 * @param string[]|string $keys Cache keys.
	 * @return bool
	 */
	public function deleteMultiple( $keys ) {
		collect( $keys )->each(
			function ( $key ) {
				return $this->delete( $key );
			}
		);

		return true;
	}

	/**
	 * Check if a cache item exists in the store.
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function has( $key ) {
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
