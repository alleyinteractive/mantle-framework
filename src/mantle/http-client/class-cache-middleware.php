<?php
/**
 * Cache_Middleware class
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use Closure;
use DateTimeInterface;

use function Mantle\Support\Helpers\normalize_cache_ttl;

/**
 * Cache Middleware for Http Client.
 *
 * Allows for simple caching of HTTP requests.
 */
class Cache_Middleware {
	/**
	 * Cache group.
	 */
	public const CACHE_GROUP = 'httpclient';

	/**
	 * Constructor.
	 *
	 * @throws \InvalidArgumentException If the TTL is not valid.
	 *
	 * @param int|DateTimeInterface|callable $ttl Time to live for the cache.
	 */
	public function __construct( protected mixed $ttl ) {
		if ( ! is_int( $ttl ) && ! $ttl instanceof DateTimeInterface && ! is_callable( $ttl ) ) { // @phpstan-ignore-line
			throw new \InvalidArgumentException(
				'TTL must be an integer, DateTimeInterface, or a callable that returns an integer.'
			);
		}
	}

	/**
	 * Invoke the middleware.
	 *
	 * @param Pending_Request $request Request to process.
	 * @param Closure         $next Next middleware in the stack.
	 * @return Response Response from the request.
	 */
	public function __invoke( Pending_Request $request, Closure $next ): Response {
		$cache_key = $this->get_cache_key( $request );
		$cache     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( $cache && $cache instanceof Response ) {
			$cache->cached = true;

			return $cache;
		}

		$response = $next( $request );

		wp_cache_set( $cache_key, $response, self::CACHE_GROUP, $this->calculate_ttl( $request, $response ) ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined

		return $response;
	}

	/**
	 * Purge the cache for a request.
	 *
	 * @param Pending_Request $request Request to purge the cache for.
	 */
	public function purge( Pending_Request $request ): bool {
		return wp_cache_delete( $this->get_cache_key( $request ), self::CACHE_GROUP );
	}

	/**
	 * Retrieve the cache key for the request.
	 *
	 * @param Pending_Request $request Request to retrieve the cache key for.
	 */
	protected function get_cache_key( Pending_Request $request ): string {
		return md5( json_encode( [ // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$request->base_url(),
			$request->url(),
			$request->method(),
			$request->body(),
			$request->headers(),
		] ) );
	}

	/**
	 * Calculate the time to live for the cache in seconds.
	 *
	 * @throws \InvalidArgumentException If the TTL callback returns an invalid value.
	 *
	 * @param Pending_Request $request Request to calculate the TTL for.
	 * @param Response        $response Response to calculate the TTL for.
	 */
	private function calculate_ttl( Pending_Request $request, Response $response ): int {
		if ( is_callable( $this->ttl ) ) {
			$callback = $this->ttl;

			$value = $callback( $request, $response );

			if ( ! is_numeric( $value ) || (int) $value < 0 ) {
				throw new \InvalidArgumentException( 'TTL callback must return a non-negative integer.' );
			}

			return (int) $value;
		}

		return normalize_cache_ttl( $this->ttl );
	}
}
