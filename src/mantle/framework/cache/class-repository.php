<?php
/**
 * Repository class file.
 *
 * @package Mantle
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Framework\Cache;

use function Mantle\Framework\Helpers\collect;

/**
 * Cache repository.
 */
abstract class Repository {
	/**
	 * Retrieve an item from cache.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return $this->pull( $key, $default );
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

}
