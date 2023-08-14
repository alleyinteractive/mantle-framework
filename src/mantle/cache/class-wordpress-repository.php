<?php
/**
 * WordPress_Repository class file.
 *
 * @package Mantle
 */

namespace Mantle\Cache;

use Mantle\Contracts\Cache\Taggable_Repository;

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
	 * Retrieve a value from cache.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ): mixed {
		$value = \wp_cache_get( $key, $this->prefix );
		return false === $value ? $default : $value;
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
	public function put( $key, $value, $ttl = null ): bool {
		return \wp_cache_set( $key, $value, $this->prefix, $ttl ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
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
	 * Remove an item from the cache.
	 *
	 * @param  string $key Cache key.
	 * @return bool
	 */
	public function forget( $key ): bool {
		return \wp_cache_delete( $key, $this->prefix );
	}

	/**
	 * Clear the cache.
	 */
	public function clear(): bool {
		return \wp_cache_flush();
	}

	/**
	 * Cache tags to apply.
	 *
	 * @param string[]|string $names Cache names.
	 * @return static
	 */
	public function tags( $names ) {
		if ( is_array( $names ) ) {
			sort( $names );
			$names = implode( $names );
		} else {
			$names = (string) $names;
		}

		if ( ! empty( $names ) ) {
			$names = "{$names}_";
		}

		return new static( $this->prefix . $names );
	}
}
