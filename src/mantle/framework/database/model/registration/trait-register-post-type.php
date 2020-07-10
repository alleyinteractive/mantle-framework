<?php
/**
 * Register_Post_Type trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Registration;

use Mantle\Framework\Database\Model\Model_Exception;

/**
 * Model Trait to allow a post type to be registered for a model.
 */
trait Register_Post_Type {
	/**
	 * Register the post type.
	 */
	public static function boot_register_post_type() {
		\add_action( 'init', [ __CLASS__, 'register_object' ] );
	}

	/**
	 * Register the post type for the model.
	 *
	 * @throws Model_Exception Thrown when registering a post type that is already registered.
	 */
	public static function register_object() {
		$post_type = static::get_object_name();

		if ( \post_type_exists( $post_type ) ) {
			throw new Model_Exception( 'Unable to register post type (post type already exists): ' . $post_type );
		}

		\register_post_type( $post_type, static::get_registration_args() );
	}
}
