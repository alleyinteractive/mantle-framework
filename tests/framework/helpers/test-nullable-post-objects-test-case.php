<?php
/**
 * Class file for Nullable_Post_Objects_Test_Case
 *
 * @package SML
 */

namespace Mantle\Tests\Framework\Helpers;

use WP_UnitTestCase;
use function Mantle\Framework\Helpers\get_post_object;

/**
 * Unit tests for nullable post object functions.
 */
class Nullable_Post_Objects_Test_Case extends WP_UnitTestCase {
	/**
	 * Test that `get_post_object()` returns a post object.
	 */
	public function test_post_object_returns_post() {
		$known_id = self::factory()->post->create();

		$found_object = get_post_object( $known_id );

		$this->assertInstanceOf( \WP_Post::class, $found_object );

		$this->assertSame( $known_id, $found_object->ID );
	}

	/**
	 * Test that `get_post_object()` honors the requested filter.
	 */
	public function test_post_object_returns_filtered_post() {
		$title = 'Post Title';

		$known_id = self::factory()->post->create(
			[
				'post_title' => $title,
			]
		);

		$this->assertSame( $title, get_post_object( $known_id, \OBJECT, 'edit' )->post_title );

		\add_filter( 'edit_post_title', '__return_empty_string' );

		$this->assertEmpty( get_post_object( $known_id, \OBJECT, 'edit' )->post_title );
	}

	/**
	 * Test that `get_post_object()` returns null when there is no post.
	 */
	public function test_post_object_returns_null() {
		$found_object = get_post_object( $this->impossible_id );

		$this->assertNull( $found_object );
	}
}
