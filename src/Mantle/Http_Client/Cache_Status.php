<?php
/**
 * Cache_Status enum file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Http_Client;

/**
 * Cache status for HTTP responses.
 */
enum Cache_Status: string {
	/**
	 * The response was not retrieved from cache and was not requested to be
	 * cached.
	 */
	case UNCACHED = 'Uncached';

	/**
	 * The response was not found in cache but was requested to be cached.
	 */
	case MISSED = 'Cache (missed)';

	/**
	 * The response was retrieved from cache.
	 */
	case CACHED = 'Cached';

	/**
	 * The response was retrieved from cache and is fresh.
	 *
	 * Applies to SWR cached responses.
	 */
	case FRESH = 'Cached (fresh)';

	/**
	 * The response was retrieved from cache but is stale.
	 *
	 * Applies to SWR cached responses.
	 */
	case STALE = 'Cached (stale)';

	/**
	 * The request was made and the response was stored in cache.
	 *
	 * Applies to SWR cached responses.
	 */
	case STORED = 'Cached (stored)';
}
