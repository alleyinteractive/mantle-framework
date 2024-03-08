<?php
/**
 * Mantle Cache Package Helpers
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

declare( strict_types=1 );

if ( ! function_exists( 'cache' ) ) {
	/**
	 * Get / set the specified cache value.
	 *
	 * If an array is passed, we'll assume you want to put to the cache.
	 *
	 * @param  mixed $args Arguments.
	 * @return mixed|\Mantle\Framework\Cache\Cache_Manager
	 *
	 * @throws \Exception
	 */
	function cache( ...$args ) {
		if ( empty( $args ) ) {
			return app( 'cache' );
		}

		if ( isset( $args[0] ) && is_string( $args[0] ) ) {
			return app( 'cache' )->get( ...$args );
		}

		if ( ! is_array( $args[0] ) ) {
			throw new Exception(
				'When setting a value in the cache, you must pass an array of key / value pairs.'
			);
		}

		return app( 'cache' )->put( key( $args[0] ), reset( $args[0] ), $args[1] ?? null );
	}
}

if ( ! function_exists( 'remember' ) ) {
	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 *
	 * @param  string                                    $key Cache key.
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl Cache TTL.
	 * @param  \Closure                                  $callback Closure to invoke.
	 * @return mixed
	 */
	function remember( string $key, $ttl, Closure $closure ) {
		return app( 'cache' )->remember( $key, $ttl, $closure );
	}
}
