<?php
/**
 * Redis_Repository class file.
 *
 * @package Mantle
 */

namespace Mantle\Cache;

use Predis\Client;
use Mantle\Contracts\Cache\Taggable_Repository;

/**
 * Redis Cache Repository
 */
class Redis_Repository extends Repository implements Taggable_Repository {
	/**
	 * Cache prefix.
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * Redis client.
	 *
	 * @var Client
	 */
	protected $client;

	/**
	 * Constructor.
	 *
	 * @param Client|array $config Configuration options or client.
	 * @param string       $prefix Prefix for caching.
	 */
	public function __construct( array|Client $config, string $prefix = '' ) {
		$this->set_connection( $config );

		$this->prefix = $prefix;
	}

	/**
	 * Set the Redis connection
	 *
	 * @param Client|array $config Configuration instance.
	 */
	protected function set_connection( Client|array $config ) {
		if ( $config instanceof Client ) {
			return $config;
		}

		$this->client = new Client( $config );
	}

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 */
	public function get( string $key, mixed $default = null ): mixed {
		$value = $this->client->get( $this->prefix . $key );

		if ( is_null( $value ) ) {
			return $default;
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Store an item in the cache.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 */
	public function put( $key, $value, $ttl = null ): bool {
		$value = maybe_serialize( $value );

		if ( is_null( $ttl ) ) {
			return (bool) $this->client->set( $this->prefix . $key, $value );
		} else {
			return (bool) $this->client->setex( $this->prefix . $key, $ttl, $value );
		}
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 * @return int|bool
	 */
	public function increment( $key, $value = 1 ) {
		return $this->client->incr( $this->prefix . $key );
	}

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 * @return int|bool
	 */
	public function decrement( $key, $value = 1 ) {
		return $this->client->decr( $this->prefix . $key );
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string $key Cache key.
	 */
	public function forget( $key ): bool {
		return (bool) $this->client->del( $key );
	}

	/**
	 * Clear the cache.
	 */
	public function clear(): bool {
		return (bool) $this->client->flushall();
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
			$names = implode( '', $names );
		} else {
			$names = (string) $names;
		}

		if ( ! empty( $names ) ) {
			$names = "{$names}_";
		}

		return new static( $this->client, $names );
	}
}
