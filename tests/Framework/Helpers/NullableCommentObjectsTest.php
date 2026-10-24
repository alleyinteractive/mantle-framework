<?php
/**
 * Class file for Nullable_Comment_Objects_Test_Case
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Helpers;

use Mantle\Testing\FrameworkTestCase;

use function Mantle\Support\Helpers\get_comment_object;

/**
 * Unit tests for nullable comment object functions.
 */
class NullableCommentObjectsTest extends FrameworkTestCase {
	/**
	 * An ID that can never belong to a real object.
	 */
	private const IMPOSSIBLE_ID = PHP_INT_MAX;

	/**
	 * Test that `get_comment_object()` returns a comment object.
	 */
	public function test_comment_object_returns_comment(): void {
		$known_id = static::factory()->comment->create();

		$this->assertIsInt( $known_id );

		$found_object = get_comment_object( $known_id );

		$this->assertInstanceOf( \WP_Comment::class, $found_object );

		$this->assertSame( $known_id, (int) $found_object->comment_ID );
	}

	/**
	 * Test that `get_comment_object()` returns null when there is no comment.
	 */
	public function test_comment_object_returns_null(): void {
		$found_object = get_comment_object( self::IMPOSSIBLE_ID );

		$this->assertNull( $found_object );
	}
}
