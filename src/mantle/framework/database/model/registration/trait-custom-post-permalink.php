<?php
/**
 * Custom_Post_Permalink trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Registration;

use function Mantle\Framework\Helpers\add_filter;

/**
 * Define custom permalink structure for post models.
 */
trait Custom_Post_Permalink {
	/**
	 * Flag if permalinks are being used.
	 *
	 * @var bool
	 */
	protected static $using_permalinks;

	/**
	 * Boot the trait and add filters for the post type link single and archive link.
	 */
	public static function boot_custom_post_permalink() {
		if ( static::get_route() ) {
			add_filter( 'post_type_link', [ __CLASS__, 'filter_post_type_link' ] );
		}

		if ( static::get_archive_route() ) {
			add_filter( 'post_type_archive_link', [ __CLASS__, 'filter_post_type_archive_link' ] );
		}

		static::$using_permalinks = ! empty( get_option( 'permalink_structure' ) );
	}

	/**
	 * Filter the post type link to allow for customization.
	 *
	 * @param string  $post_link The post's permalink.
	 * @param \WP_Post $post     The post in question.
	 */
	public static function filter_post_type_link( string $post_link, \WP_Post $post ): string {
		if ( ! static::$using_permalinks || $post->post_type !== static::get_object_name() ) {
			return $post_link;
		}

		$route = static::get_route();

		// todo: abstract a bit.
		return home_url( str_replace( '{slug}', $post->post_name, $route ) );
	}

	/**
	 * Filter the post type archive link.
	 *
	 * @param string $link Post type archive link.
	 * @param string $post_type Post type.
	 * @return string
	 */
	public static function filter_post_type_archive_link( string $link, string $post_type ): string {
		if ( 'post' === $post_type || ! static::$using_permalinks || $post_type !== static::get_object_name() ) {
			return $link;
		}

		// todo: abstract a bit.
		return home_url( static::get_archive_route() );
	}
}
