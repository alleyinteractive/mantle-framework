<?php
/**
 * Caches_Requests trait file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client\Concerns;

use Closure;
use DateTimeInterface;
use InvalidArgumentException;
use Mantle\Http_Client\Cache_Flexible_Middleware;
use Mantle\Http_Client\Cache_Middleware;
use Mantle\Http_Client\Http_Method;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Response;

use function Mantle\Support\Helpers\collect;

/**
 * Caches requests using the Cache_Middleware for the Http Client.
 *
 * @mixin \Mantle\Http_Client\Pending_Request
 */
trait Caches_Requests {
	/**
	 * Enable caching for the request.
	 *
	 * @param int|DateTimeInterface|Closure $ttl Time to live for the cache.
	 * @param string|null                   $key Cache key to use.
	 * @phpstan-param int|DateTimeInterface|(Closure(Pending_Request $request, Response $response): int|DateTimeInterface) $ttl
	 */
	public function cache( int|DateTimeInterface|Closure $ttl = 3600, ?string $key = null ): static {
		return $this
			->filter_middleware( fn ( callable $middleware ) => ! $middleware instanceof Cache_Middleware )
			->prepend_middleware( new Cache_Middleware( $ttl, $key ) );
	}

	/**
	 * Enable flexible caching for the request.
	 *
	 * This will use a stale-while-revalidate strategy to return a cached response if it exists,
	 * even if it is stale, while refreshing the cache in the background.
	 *
	 * @param int|\DateInterval|\DateTimeInterface|Closure $stale Time to consider a cached response stale.
	 * @param int|\DateInterval|\DateTimeInterface|Closure $expire Time to consider a cached response expired.
	 * @param string|null                                  $key Cache key to use.
	 *
	 * @phpstan-param int|DateTimeInterface|(Closure(Pending_Request $request, Response $response): int|DateTimeInterface) $stale
	 * @phpstan-param int|DateTimeInterface|(Closure(Pending_Request $request, Response $response): int|DateTimeInterface) $expire
	 */
	public function cache_flexible( int|\DateInterval|\DateTimeInterface|Closure $stale, int|\DateInterval|\DateTimeInterface|Closure $expire, ?string $key = null ): static {
		return $this
			->filter_middleware( fn ( callable $middleware ) => ! $middleware instanceof Cache_Middleware )
			->prepend_middleware( new Cache_Flexible_Middleware( $stale, $expire, $key ) );
	}

	/**
	 * Purge the cache for the request.
	 *
	 * @throws InvalidArgumentException If the request has no URL or is not cached.
	 *
	 * @param string|null             $url URL to purge, optional.
	 * @param string|Http_Method|null $method Method to purge, optional.
	 */
	public function purge( ?string $url = null, string|Http_Method|null $method = null ): bool {
		if ( ! is_null( $url ) ) {
			$this->set_url( $url );
		}

		if ( ! is_null( $method ) ) {
			$this->set_method( $method );
		}

		if ( empty( $this->url ) ) {
			throw new InvalidArgumentException( 'Cannot purge cache for a request that has no URL. Call url() first.' );
		}

		$middleware = collect( $this->middleware )->first( fn ( $middleware ) => $middleware instanceof Cache_Middleware );

		if ( ! $middleware ) {
			throw new InvalidArgumentException( 'Cannot purge cache for a request that is not cached. Call cache() first.' );
		}

		assert( $middleware instanceof Cache_Middleware );

		return $middleware->purge( $this );
	}
}
