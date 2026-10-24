<?php
/**
 * Makes_Http_Requests_With_Sitemaps trait file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

/**
 * Ensure that HTTP requests in unit tests handle feed testing properly.
 *
 * Similar to Makes_Http_Requests_With_Templates, core uses locate_template() to load feed templates. This is great for
 * the first test but because `$load_once` is true by default, subsequent tests in the same request will not load the
 * feed templates subsequently. This trait ensures that feed templates are loaded properly each time.
 *
 * @mixin Makes_Http_Requests
 *
 * @phpstan-type Feed_Hook_Array array{0: string, 1: string}
 */
trait Makes_Http_Requests_With_Feeds {
	/**
	 * Core feed hooks to be handled by the trait.
	 *
	 * @var Feed_Hook_Array[]
	 */
	private array $core_feed_hooks = [
		'rdf'  => [
			'do_feed_rdf',
			'feed-rdf.php',
		],
		'rss'  => [
			'do_feed_rss',
			'feed-rss.php',
		],
		'rss2' => [
			'do_feed_rss2',
			'feed-rss2.php',
		],
		'atom' => [
			'do_feed_atom',
			'feed-atom.php',
		],
	];

	/**
	 * Custom feed hooks to be handled by the trait.
	 *
	 * For use in projects extending this trait to add their own custom feed handlers.
	 *
	 * @var Feed_Hook_Array[]
	 */
	protected array $custom_feed_hooks = [];

	/**
	 * Storage of the rendered feeds.
	 *
	 * @var array<string, bool>
	 */
	protected static array $feeds_rendered = [];

	/**
	 * Set up the trait.
	 *
	 * @todo Convert to Before attribute when PHPUnit 12 is minimum.
	 */
	public function makes_http_requests_with_feeds_set_up(): void {
		$hooks = array_merge( $this->core_feed_hooks, $this->custom_feed_hooks );

		foreach ( $hooks as $type => [ $hook, $template ] ) {
			add_action( $hook, $this->generate_feed_callback( $type, $template ), 20 );
		}
	}

	/**
	 * Generate a callback to be passed to the feed action for the given template.
	 *
	 * @param string $type     The feed type to generate the callback for.
	 * @param string $template The template to generate the callback for.
	 */
	protected function generate_feed_callback( string $type, string $template ): \Closure {
		return function () use ( $type, $template ): void {
			// If the feed was not rendered yet, mark it as rendered and bail.
			if ( ! isset( static::$feeds_rendered[ $type ] ) || ! static::$feeds_rendered[ $type ] ) {
				static::$feeds_rendered[ $type ] = true;

				return;
			}

			// Allow a custom feed template to be passed. Otherwise, default to the templates
			// in wp-includes.
			if ( ! file_exists( $template ) ) {
				$template = ABSPATH . WPINC . "/{$template}";
			}

			load_template( $template, false );

			static::$feeds_rendered[ $type ] = true;
		};
	}
}
