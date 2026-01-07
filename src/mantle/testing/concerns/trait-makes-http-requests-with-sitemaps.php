<?php
declare(strict_types=1);

/**
 * Makes_Http_Requests_With_Sitemaps trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Doubles\Sitemaps\Spy_Sitemaps_Renderer;

/**
 * Ensure that HTTP requests in unit tests handle WordPress sitemaps properly.
 *
 * @mixin Makes_Http_Requests
 */
trait Makes_Http_Requests_With_Sitemaps {
	/**
	 * Set up the trait.
	 *
	 * @todo Convert to Before attribute when PHPUnit 12 is minimum..
	 */
	public function makes_http_requests_with_sitemaps_set_up(): void {
		// Replace the server's renderer with a spy.
		wp_sitemaps_get_server()->renderer = new Spy_Sitemaps_Renderer();
	}
}
