<?php
/**
 * Custom_Permalink trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Registration;

use RuntimeException;

trait Custom_Permalink {
	/**
	 * Boot the trait.
	 */
	public static function boot_custom_permalink() {
		if ( static::get_route() ) {
			add_filter( 'post_type_link', [ __CLASS__, 'filter_post_type_link' ], 99 );
		}
	}

	/**
	 * Filter the post type link to allow for customization.
	 *
	 * @param string  $post_link The post's permalink.
	 * @param \WP_Post $post     The post in question.
	 */
	public static function filter_post_type_link( string $post_link, \WP_Post $post ) {
		if ( $post->post_type !== static::get_object_name() ) {
			return $post_link;
		}

		$route = static::get_route();

		if ( ! $route ) {
			throw new RuntimeException( 'Undefined route for model: ' . __CLASS__ );
		}

		// todo: abstract a bit.
		return home_url( str_replace( '{post}', $post->post_name, $route ) );
	}
}
