<?php
namespace Mantle\Testing\Doubles\Sitemaps;

use function Mantle\Support\Helpers\terminate_request;

/**
 * Spy Sitemaps Renderer
 *
 * A spy class for WP_Sitemaps_Renderer that terminates the request after rendering
 * to prevent actual output during tests.
 */
class Spy_Sitemaps_Renderer extends \WP_Sitemaps_Renderer {
	/**
	 * Renders a sitemap index.
	 *
	 * @param array<mixed> $sitemaps Array of sitemap data.
	 */
	public function render_index( $sitemaps ): never {
		parent::render_index( $sitemaps );

		terminate_request( headers: [
			'Content-Type' => 'application/xml; charset=utf-8',
		] );
	}

	/**
	 * Renders a sitemap.
	 *
	 * @param array<mixed> $url_list Array of URLs for a sitemap.
	 */
	public function render_sitemap( $url_list ): never {
		parent::render_sitemap( $url_list );

		terminate_request( headers: [
			'Content-Type' => 'application/xml; charset=utf-8',
		] );
	}
}
