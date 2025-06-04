<?php
/**
 * Mantle Cache Package Helpers
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

declare(strict_types=1);

if ( ! function_exists( 'cache' ) ) {
	/**
	 * Get / set the specified cache value.
	 *
	 * If an array is passed, we'll assume you want to put to the cache.
	 *
	 * @param  array<mixed>|string|null $key Cache key or array of a key / value pair to set.
	 * @param  mixed                    $default Default value to return if the key does not exist.
	 * @phpstan-return ($key is null ? \Mantle\Cache\Repository : mixed)
	 *
	 * @throws \Exception
	 */
	function cache( array|string|null $key = null, mixed $default = null ) {
		/** @var \Mantle\Cache\Repository $cache */
		$cache = app( 'cache' );

		if ( is_null( $key ) ) {
			return $cache;
		}

		if ( is_string( $key ) ) {
			return $cache->get( $key, $default );
		}

		if ( [] === $key || ! is_string( key( $key ) ) ) {
			throw new Exception(
				'When setting a value in the cache, you must pass an array of key / value pairs.'
			);
		}

		if ( ! $default instanceof DateTimeInterface && ! is_int( $default ) ) {
			throw new Exception(
				'When setting a value in the cache, the expiration passed to $default must be a DateTimeInterface or an integer.'
			);
		}

		return $cache->put( key( $key ), reset( $key ), $default );
	}
}

if ( ! function_exists( 'remember' ) ) {
	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 *
	 * @param  string                                    $key Cache key.
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl Cache TTL.
	 * @param  \Closure                                  $callback Closure to invoke.
	 */
	function remember( string $key, $ttl, Closure $closure ): mixed {
		return cache()->remember( $key, $ttl, $closure );
	}
}
