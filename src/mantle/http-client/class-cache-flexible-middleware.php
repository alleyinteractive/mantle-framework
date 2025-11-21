<?php
/**
 * Cache_Flexible_Middleware class
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use Closure;
use DateTimeInterface;
use LogicException;
use Mantle\Cache\SWR_Storage;

use function Mantle\Support\Helpers\defer;
use function Mantle\Support\Helpers\normalize_cache_ttl;

/**
 * Cache Middleware for Http Client.
 *
 * Allows for simple caching of HTTP requests.
 *
 * @todo Add support for fully asynchronous stale-while-revalidate caching that
 * uses the cron instead of defer().
 */
class Cache_Flexible_Middleware extends Cache_Middleware {
	/**
	 * Cache key.
	 */
	private string $cache_key;

	/**
	 * Constructor.
	 *
	 * @param int|\DateInterval|\DateTimeInterface|\Closure $stale Time to consider a cached response stale.
	 * @param int|\DateInterval|\DateTimeInterface|\Closure $expire Time to consider a cached response expired.
	 * @param string|null                                   $key Cache key to use.
	 */
	public function __construct( protected int|\DateInterval|\DateTimeInterface|\Closure $stale, protected int|\DateInterval|\DateTimeInterface|\Closure $expire, public readonly ?string $key = null ) {}

	/**
	 * Invoke the middleware.
	 *
	 * @param Pending_Request $request Request to process.
	 * @param Closure         $next Next middleware in the stack.
	 * @return Response Response from the request.
	 */
	public function __invoke( Pending_Request $request, Closure $next ): Response {
		$this->cache_key = $this->get_cache_key( $request );

		$cache = $this->get_cached_response();

		if ( $cache instanceof SWR_Storage && $cache->value instanceof Response ) {
			$response = $cache->value;

			// If the cache is stale, we can still return it, but we should refresh
			// deferred to the end of the request.
			if ( $cache->is_stale() ) {
				$fresh_request = ( clone $request )->without_middleware( Cache_Middleware::class );

				defer(
					fn () => $this->store_response( $fresh_request, $fresh_request->send() ),
				);
			}

			$response->cached = true;

			return $response;
		}

		$response = $next( $request );

		assert( $response instanceof Response );

		$this->store_response( request: $request, response: $response );

		return $response;
	}

	/**
	 * Retrieve a cached response if available.
	 */
	private function get_cached_response(): ?SWR_Storage {
		try {
			$cache = wp_cache_get( $this->cache_key, self::CACHE_GROUP );

			if ( $cache && $cache instanceof SWR_Storage ) {
				return $cache;
			}

			return null;
		} catch ( LogicException ) {
			return null;
		}
	}

	/**
	 * Store a response in the cache.
	 *
	 * @throws \InvalidArgumentException If the stale time is not less than the expire time.
	 *
	 * @param Pending_Request $request Request associated with the response.
	 * @param Response        $response Response to store.
	 */
	private function store_response( Pending_Request $request, Response $response ): bool {
		if ( $this->stale instanceof Closure ) {
			$this->stale = $this->invoke_expiration_callback( $this->stale, $request, $response );
		}

		if ( $this->expire instanceof Closure ) {
			$this->expire = $this->invoke_expiration_callback( $this->expire, $request, $response );
		}

		$stale_time  = normalize_cache_ttl( $this->stale );
		$expire_time = normalize_cache_ttl( $this->expire );

		if ( $stale_time >= $expire_time ) {
			throw new \InvalidArgumentException( 'Stale time must be less than expire time for flexible caching.' );
		}

		return wp_cache_set(
			$this->cache_key,
			new SWR_Storage(
				value: $response,
				stale_time: time() + $stale_time,
			),
			self::CACHE_GROUP,
			normalize_cache_ttl( $expire_time ), // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
		);
	}
}
