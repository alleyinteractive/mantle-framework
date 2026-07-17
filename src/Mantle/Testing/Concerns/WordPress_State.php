<?php
/**
 * This file contains the WordPress_State trait
 *
 * phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use DateTimeInterface;
use Mantle\Database\Model\Post;
use Mantle\Testing\Attributes\PermalinkStructure;
use Mantle\Testing\Utils;
use PHPUnit\Framework\Attributes\Before;
use ReflectionAttribute;
use WP_Post;

/**
 * This trait includes functionality for controlling WordPress state during
 * testing.
 */
trait WordPress_State {
	use Interacts_With_Attributes;

	/**
	 * Whether the initial data structures have been created.
	 */
	private static bool $initial_data_structures_created = false;

	/**
	 * Set up the WordPress State before the class is set up.
	 */
	public static function wordpress_state_set_up_before_class(): void {
		// Set the default permalink structure on each test before setUp() to allow
		// the tests to override it.
		self::set_permalink_structure( Utils::DEFAULT_PERMALINK_STRUCTURE );

		if ( ! self::$initial_data_structures_created ) {
			// Create the initial post types/taxonomies after the default permalink
			// structure is set.
			create_initial_post_types();
			create_initial_taxonomies();

			flush_rewrite_rules(); // phpcs:ignore

			self::$initial_data_structures_created = true;
		}
	}

	/**
	 * Register the PermalinkStructure attribute.
	 */
	#[Before]
	public function register_permalink_structure_attribute(): void {
		$this->register_attribute(
			PermalinkStructure::class,
			fn ( ReflectionAttribute $attribute ) => $this->set_permalink_structure( $attribute->newInstance()->structure ),
		);
	}

	/**
	 * Cleans the global scope (e.g `$_GET` and `$_POST`).
	 */
	public static function clean_up_global_scope(): void {
		$_COOKIE  = [];
		$_GET     = [];
		$_POST    = [];
		$_REQUEST = [];
		$_SESSION = [];

		self::flush_cache();
	}

	/**
	 * Flushes the WordPress object cache.
	 */
	public static function flush_cache(): void {
		Utils::flush_cache();
	}

	/**
	 * Unregister existing post types and register defaults.
	 *
	 * Run before each test in order to clean up the global scope, in case
	 * a test forgets to unregister a post type on its own, or fails before
	 * it has a chance to do so.
	 */
	protected function reset_post_types(): void {
		foreach ( get_post_types( [], 'objects' ) as $pt ) {
			if ( empty( $pt->tests_no_auto_unregister ) ) {
				unregister_post_type( $pt->name );
			}
		}

		create_initial_post_types();
	}

	/**
	 * Unregister existing taxonomies and register defaults.
	 *
	 * Run before each test in order to clean up the global scope, in case
	 * a test forgets to unregister a taxonomy on its own, or fails before
	 * it has a chance to do so.
	 */
	protected function reset_taxonomies(): void {
		foreach ( get_taxonomies() as $tax ) {
			unregister_taxonomy( $tax );
		}

		create_initial_taxonomies();
	}

	/**
	 * Unregister non-built-in post statuses.
	 */
	protected function reset_post_statuses(): void {
		foreach ( get_post_stati( [ '_builtin' => false ] ) as $post_status ) {
			Utils::unregister_post_status( $post_status );
		}
	}

	/**
	 * Clean up any registered meta keys.
	 *
	 * @global array $wp_meta_keys
	 */
	public static function unregister_all_meta_keys(): void {
		global $wp_meta_keys;
		if ( ! is_array( $wp_meta_keys ) ) {
			return;
		}

		foreach ( $wp_meta_keys as $object_type => $type_keys ) {
			foreach ( $type_keys as $object_subtype => $subtype_keys ) {
				foreach ( $subtype_keys as $key => $value ) {
					unregister_meta_key( $object_type, $key, $object_subtype );
				}
			}
		}
	}

	/**
	 * Deletes a user from the database in a Multisite-agnostic way.
	 *
	 * @since 4.3.0
	 *
	 * @param int $user_id User ID.
	 * @return bool True if the user was deleted.
	 */
	public static function delete_user( $user_id ): bool {
		if ( is_multisite() ) {
			return wpmu_delete_user( $user_id );
		}

		return wp_delete_user( $user_id );
	}

	/**
	 * Resets permalinks and flushes rewrites.
	 *
	 * @since 4.4.0
	 *
	 * @global \WP_Rewrite $wp_rewrite
	 *
	 * @param string $structure Optional. Permalink structure to set. Default empty.
	 */
	public static function set_permalink_structure( $structure = '' ): void {
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $structure );
		$wp_rewrite->flush_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions
	}

	/**
	 * Updates the modified and modified GMT date of a post in the database.
	 *
	 * @throws \InvalidArgumentException If the post type cannot be resolved.
	 *
	 * @param WP_Post|Post|int         $post Post ID or post object.
	 * @param DateTimeInterface|string $date Date object or string to update the
	 *                                       post with. If a string is passed it
	 *                                       is assumed to be local timezone.
	 */
	protected function update_post_modified( WP_Post|Post|int $post, DateTimeInterface|string $date ): bool {
		$post = match ( true ) {
			$post instanceof WP_Post => Post::for( $post->post_type )->find_or_fail( $post->ID ),
			$post instanceof Post    => $post,
			default                  => get_post_type( $post ) ? Post::for( get_post_type( $post ) )->find_or_fail( $post ) : throw new \InvalidArgumentException( 'Unresolvable post type.' ),
		};

		return $post->save(
			[
				'post_modified' => $date instanceof DateTimeInterface ? $date->format( 'Y-m-d H:i:s' ) : $date,
			]
		);
	}

	/**
	 * Sets the site to show posts on the front page.
	 */
	protected function set_show_posts_on_front(): void {
		update_option( 'show_on_front', 'posts' );

		delete_option( 'page_on_front' );
		delete_option( 'page_for_posts' );
	}

	/**
	 * Sets the site to show a static page on the front page.
	 *
	 * @param int|WP_Post|Post      $front Front page.
	 * @param int|WP_Post|Post|null $posts  Posts page.
	 */
	public function set_show_page_on_front( int|WP_Post|Post $front, int|WP_Post|Post|null $posts = null ): void {
		update_option( 'show_on_front', 'page' );

		update_option(
			'page_on_front',
			match ( true ) {
				$front instanceof WP_Post => $front->ID,
				$front instanceof Post    => $front->id(),
				default                  => $front,
			},
		);

		if ( null !== $posts ) {
			update_option(
				'page_for_posts',
				match ( true ) {
					$posts instanceof WP_Post => $posts->ID,
					$posts instanceof Post    => $posts->id(),
					default                   => $posts,
				},
			);
		} else {
			delete_option( 'page_for_posts' );
		}
	}
}
