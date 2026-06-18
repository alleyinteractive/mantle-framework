<?php
/**
 * Spy_Sitemaps_Renderer class file
 *
 * phpcs:disable
 *
 * @package Mantle
 */

namespace Mantle\Testing\Doubles\Sitemaps;

use function Mantle\Support\Helpers\terminate_request;

/**
 * Spy Sitemaps Renderer
 *
 * A spy class for WP_Sitemaps_Renderer that terminates the request after
 * rendering to prevent actual output during tests.
 *
 * The render methods are overridden to call the original methods and then
 * terminate the request immediately after, simulating the behavior of WordPress
 * in a test environment. Other methods pass through to the original renderer.
 */
class Spy_Sitemaps_Renderer extends \WP_Sitemaps_Renderer {
	/**
	 * Constructor.
	 *
	 * @param \WP_Sitemaps_Renderer $original_renderer The original renderer to spy on.
	 */
	public function __construct( public readonly \WP_Sitemaps_Renderer $original_renderer ) {
		parent::__construct();
	}

	/**
	 * Renders a sitemap index.
	 *
	 * @param array<mixed> $sitemaps Array of sitemap data.
	 */
	public function render_index( $sitemaps ): never {
		$this->original_renderer->render_index( $sitemaps );

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
		$this->original_renderer->render_sitemap( $url_list );

		terminate_request( headers: [
			'Content-Type' => 'application/xml; charset=utf-8',
		] );
	}

	// Pass through methods:

	public function get_sitemap_stylesheet_url(): mixed {
		return $this->original_renderer->get_sitemap_stylesheet_url();
	}

	public function get_sitemap_index_stylesheet_url(): mixed {
		return $this->original_renderer->get_sitemap_index_stylesheet_url();
	}

	public function get_sitemap_index_xml( $sitemaps ): mixed {
		return $this->original_renderer->get_sitemap_index_xml( $sitemaps );
	}

	public function get_sitemap_xml( $url_list ): mixed {
		return $this->original_renderer->get_sitemap_xml( $url_list );
	}
}
