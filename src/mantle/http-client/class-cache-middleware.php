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
	 * @param int|DateTimeInterface|Closure $ttl Time to live for the cache.
	 * @param string|null                   $key Cache key to use.
	 */
	public function __construct( protected int|DateTimeInterface|Closure $ttl, public readonly ?string $key = null ) {}

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
		return $this->key ?? md5( (string) json_encode( [ // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
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
	 * @param Pending_Request $request Request to calculate the TTL for.
	 * @param Response        $response Response to calculate the TTL for.
	 */
	private function calculate_ttl( Pending_Request $request, Response $response ): int {
		$ttl = $this->ttl;

		if ( $ttl instanceof Closure ) {
			$ttl = $this->invoke_expiration_callback( $ttl, $request, $response );
		}

		return normalize_cache_ttl( $ttl );
	}

	/**
	 * Invoke the callback with the request and response and validate the return type.
	 *
	 * @throws \InvalidArgumentException If the callback does not return an integer or DateTimeInterface.
	 *
	 * @param Closure         $callback Callback to invoke.
	 * @param Pending_Request $request Request to pass to the callback.
	 * @param Response        $response Response to pass to the callback.
	 */
	protected function invoke_expiration_callback( Closure $callback, Pending_Request $request, Response $response ): int|DateTimeInterface {
		$value = $callback( $request, $response );

		if ( ! is_int( $value ) && ! $value instanceof DateTimeInterface ) {
			throw new \InvalidArgumentException( 'Callback must return an integer or DateTimeInterface.' );
		}

		return $value;
	}
}
