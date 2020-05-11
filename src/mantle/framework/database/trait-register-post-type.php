<?php
/**
 * Register_Post_Type trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database;

use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Support\Arr;

/**
 * Model Trait to allow a post type to be registered for a model.
 */
trait Register_Post_Type {
	/**
	 * Post type name.
	 *
	 * @var string
	 */
	protected static $post_type;

	/**
	 * Register the post type.
	 */
	public static function register() {
		$post_type = static::get_registrable_post_type();

		if ( \post_type_exists( $post_type ) ) {
			throw new Model_Exception( 'Unable to register post type (post type already exists): ' . $post_type );
		}

		\register_post_type( $post_type, static::get_post_type_arguments() );
	}

	/**
	 * Get the post type to register.
	 *
	 * @return string
	 */
	protected static function get_registrable_post_type(): string {
		if ( ! empty( static::$post_type ) ) {
			return (string) static::$post_type;
		}

		return strtolower( Arr::last( explode( '\\', __CLASS__ ) ) );
	}

	/**
	 * Get the arguments for creating a post type.
	 *
	 * @return array
	 */
	protected static function get_post_type_arguments(): array {
		return array_merge(
			[
				// 'public' => true,
			],
		);
	}
}
