<?php
/**
 * Interacts_With_Feeds trait file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Http_Client\Concerns;

use WP_SimplePie_Sanitize_KSES;

/**
 * Integrations with feed responses.
 *
 * @mixin \Mantle\Http_Client\Response
 */
trait Interacts_With_Feeds {
	/**
	 * Check if the response is a feed.
	 */
	public function is_feed(): bool {
		return false !== strpos( (string) $this->header( 'content-type' ), 'application/rss+xml' ) ||
			false !== strpos( (string) $this->header( 'content-type' ), 'application/atom+xml' ) ||
			false !== strpos( (string) $this->header( 'content-type' ), 'application/xml' ) ||
			false !== strpos( (string) $this->header( 'content-type' ), 'text/xml' );
	}

	/**
	 * Retrieve the feed response as a SimplePie object.
	 *
	 * Mirrors the functionality of core's fetch_feed() function.
	 *
	 * @see \fetch_feed()
	 * @link https://simplepie.org/wiki/reference/simplepie/start
	 */
	public function feed(): \SimplePie {
		if ( ! class_exists( \SimplePie::class, false ) ) {
			require_once ABSPATH . WPINC . '/class-simplepie.php';
		}

		if ( ! class_exists( WP_SimplePie_Sanitize_KSES::class, false ) ) {
			require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
		}

		$feed = class_exists( \SimplePie\SimplePie::class ) ? new \SimplePie\SimplePie() : new \SimplePie();

		/**
		 * Mirrors core:
		 *
		 * > We must manually overwrite $feed->sanitize because SimplePie's
		 * > constructor sets it before we have a chance to set the sanitization
		 * > class.
		 */
		$feed->sanitize = new \WP_SimplePie_Sanitize_KSES();

		$feed->set_raw_data( $this->body() );
		$feed->init();
		$feed->set_output_encoding( get_bloginfo( 'charset' ) );

		return $feed;
	}
}
