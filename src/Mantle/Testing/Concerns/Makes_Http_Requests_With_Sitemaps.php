<?php
/**
 * Makes_Http_Requests_With_Sitemaps trait file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Doubles\Sitemaps\Spy_Sitemaps_Renderer;
use PHPUnit\Framework\Attributes\Before;

/**
 * Ensure that HTTP requests in unit tests handle WordPress sitemaps properly.
 *
 * @mixin Makes_Http_Requests
 */
trait Makes_Http_Requests_With_Sitemaps {
	/**
	 * Set up the trait.
	 *
	 * Runs after setUp() so the Hooks trait has already backed up the hooks this adds.
	 */
	#[Before( -10 )]
	public function makes_http_requests_with_sitemaps_set_up(): void {
		$server = wp_sitemaps_get_server();

		// Replace the server's renderer with a spy.
		$server->renderer = new Spy_Sitemaps_Renderer( $server->renderer );
	}
}
