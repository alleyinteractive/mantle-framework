<?php
/**
 * WordPress_Cache_Repository class file.
 *
 * @package Mantle
 */

namespace Mantle\Cache;

use Mantle\Contracts\Cache\Taggable_Repository;

/**
 * WordPress Cache Repository
 */
class WordPress_Cache_Repository extends Repository implements Taggable_Repository {
	/**
	 * Constructor.
	 *
	 * @param string $prefix Prefix for caching.
	 */
	public function __construct( protected string $prefix = '' ) {}

	/**
	 * Retrieve a value from cache.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $default Default value.
	 */
	public function get( string $key, mixed $default = null ): mixed {
		$value = \wp_cache_get( $key, $this->prefix );

		return false === $value ? $default : $value;
	}

	/**
	 * Retrieve multiple cache keys.
	 *
	 * @param iterable $keys Cache keys.
	 * @param mixed    $default Default value.
	 */
	public function get_multiple( iterable $keys, mixed $default = null ): iterable {
		return \wp_cache_get_multiple( $keys, $this->prefix );
	}

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 */
	public function pull( string $key, mixed $default = null ): mixed {
		$value = \wp_cache_get( $key, $this->prefix );

		if ( false !== $value ) {
			\wp_cache_delete( $key, $this->prefix );
		}

		return false === $value ? $default : $value;
	}

	/**
	 * Set a cache item.
	 *
	 * @param string                 $key Cache key.
	 * @param mixed                  $value Item value.
	 * @param null|int|\DateInterval $ttl TTL.
	 */
	public function set( string $key, mixed $value, int|\DateInterval|\DateTimeInterface|null $ttl = null ): bool {
		return \wp_cache_set( $key, $value, $this->prefix, $this->normalize_ttl( $ttl ) ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
	}

	/**
	 * Set multiple keys.
	 *
	 * @param iterable                                  $values Key value pair of values to set.
	 * @param null|int|\DateInterval|\DateTimeInterface $ttl Cache TTL.
	 */
	public function set_multiple( iterable $values, null|int|\DateInterval|\DateTimeInterface $ttl = null ): bool {
		$result = \wp_cache_set_multiple( $values, $this->prefix, $this->normalize_ttl( $ttl ) ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined

		return ! in_array( false, $result, true );
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 */
	public function increment( string $key, int $value = 1 ): int|bool {
		return \wp_cache_incr( $key, $value, $this->prefix );
	}

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 */
	public function decrement( string $key, int $value = 1 ): int|bool {
		return \wp_cache_decr( $key, $value, $this->prefix );
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string $key Cache key.
	 */
	public function delete( string $key ): bool {
		return \wp_cache_delete( $key, $this->prefix );
	}

	/**
	 * Delete multiple cache keys.
	 *
	 * @param iterable<string> $keys Cache keys.
	 */
	public function delete_multiple( iterable $keys ): bool {
		$result = \wp_cache_delete_multiple( $keys, $this->prefix );

		return ! in_array( false, $result, true );
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
	 */
	public function tags( array|string $names ): static {
		if ( is_array( $names ) ) {
			sort( $names );
			$names = implode( '', $names );
		}

		return new static( $this->prefix . $names );
	}

	/**
	 * Convert the TTL to seconds.
	 *
	 * @param int|\DateTimeInterface|\DateInterval|null $ttl
	 */
	protected function normalize_ttl( int|\DateTimeInterface|\DateInterval|null $ttl ): int {
		if ( $ttl instanceof \DateTimeInterface ) {
			$ttl = $ttl->getTimestamp() - time();
		} elseif ( $ttl instanceof \DateInterval ) {
			$ttl = ( new \DateTimeImmutable() )->add( $ttl )->getTimestamp() - time();
		} elseif ( null === $ttl ) {
			$ttl = 0;
		}

		return $ttl;
	}
}
