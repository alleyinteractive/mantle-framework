<?php
/**
 * Array_Repository class file.
 *
 * @package Mantle
 */

namespace Mantle\Cache;

use Carbon\Carbon;
use Mantle\Contracts\Cache\Repository as Repository_Contract;

/**
 * Array Cache Repository
 */
class Array_Repository extends Repository implements Repository_Contract {
	/**
	 * Cache storage.
	 *
	 * @var array
	 */
	protected $storage = [];

	/**
	 * Retrieve a value from cache.
	 *
	 * @template TCacheValue
	 *
	 * @param string                                $key Cache key.
	 * @param TCacheValue|(\Closure(): TCacheValue) $default Default value.
	 * @return (TCacheValue is null ? mixed : TCacheValue)
	 */
	public function get( string $key, mixed $default = null ): mixed {
		if ( ! isset( $this->storage[ $key ] ) ) {
			return $default;
		}

		$item       = $this->storage[ $key ];
		$expires_at = $item['expires_at'] ?? 0;

		if ( 0 !== $expires_at && Carbon::now()->getTimestamp() > $expires_at ) {
			unset( $this->storage[ $key ] );

			return $default;
		}

		return $item['value'] ?? $default;
	}
	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function pull( $key, $default = null ) {
		$value = $this->get( $key, $default );
		unset( $this->storage[ $key ] );

		return $value;
	}

	/**
	 * Store an item in the cache.
	 *
	 * @param  string                                    $key
	 * @param  mixed                                     $value
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl
	 */
	public function put( $key, $value, $ttl = null ): bool {
		$this->storage[ $key ] = [
			'expire_at' => Carbon::now()->addSeconds( $ttl ?: 0 )->getTimestamp(),
			'value'     => $value,
		];

		return true;
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 * @return int|bool
	 */
	public function increment( $key, $value = 1 ) {
		$value = $this->get( $key, 0 );
		return $this->set( $key, $value += $value );
	}

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string $key Cache key.
	 * @param  int    $value Value.
	 * @return int|bool
	 */
	public function decrement( $key, $value = 1 ) {
		$value = $this->get( $key, 0 );
		return $this->set( $key, $value -= $value );
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string $key Cache key.
	 */
	public function forget( $key ): bool {
		unset( $this->storage[ $key ] );
		return true;
	}

	/**
	 * Clear the cache.
	 */
	public function clear(): bool {
		$this->storage = [];
		return true;
	}
}
