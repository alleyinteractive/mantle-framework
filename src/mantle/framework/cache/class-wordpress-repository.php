<?php
/**
 * WordPress_Repository class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Cache;

use Closure;
use Mantle\Framework\Contracts\Cache\Taggable_Repository;

/**
 * WordPress Cache Repository
 */
class WordPress_Repository extends Repository implements Taggable_Repository {
	/**
	 * Cache prefix.
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * Constructor.
	 *
	 * @param string $prefix Prefix for caching.
	 */
	public function __construct( string $prefix = '' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function pull( $key, $default = null ) {
		$value = \wp_cache_get( $key, $this->prefix );

		if ( false !== $value ) {
			\wp_cache_delete( $key, $this->prefix );
		}

		return false === $value ? $default : $value;
	}

	/**
	 * Store an item in the cache.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 * @return bool
	 */
	public function put( $key, $value, $ttl = null ) {
		return \wp_cache_set( $key, $value, $this->prefix, $ttl );
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
	 * Increment the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 * @return int|bool
	 */
	public function increment( $key, $value = 1 ) {
		return \wp_cache_incr( $key, $value, $this->prefix );
	}

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 * @return int|bool
	 */
	public function decrement( $key, $value = 1 ) {
		return \wp_cache_decr( $key, $value, $this->prefix );
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

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string $key Cache key.
	 * @return bool
	 */
	public function forget( $key ) {
		return \wp_cache_delete( $key, $this->prefix );
	}

	/**
	 * Clear the cache.
	 */
	public function clear() {
		return \wp_cache_flush();
	}

	/**
	 * Cache tags to apply.
	 *
	 * @param string[]|string $names Cache names.
	 * @return static
	 */
	public function tags( $names ) {
		$names = is_array( $names ) ? implode( $names ) : $names;
		return new static( $this->prefix . $names );
	}
}
