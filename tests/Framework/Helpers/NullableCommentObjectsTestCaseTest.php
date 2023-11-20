<?php
/**
 * Class file for Nullable_Comment_Objects_Test_Case
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Helpers;

use Mantle\Testing\Framework_Test_Case;

use function Mantle\Support\Helpers\get_comment_object;

/**
 * Unit tests for nullable comment object functions.
 */
class NullableCommentObjectsTestCaseTest extends Framework_Test_Case {
	/**
	 * Test that `get_comment_object()` returns a comment object.
	 */
	public function test_comment_object_returns_comment() {
		$known_id = self::factory()->comment->create();

		$found_object = get_comment_object( $known_id );

		$this->assertInstanceOf( \WP_Comment::class, $found_object );

		$this->assertSame( $known_id, (int) $found_object->comment_ID );
	}

	/**
	 * Test that `get_comment_object()` returns null when there is no comment.
	 */
	public function test_comment_object_returns_null() {
		$found_object = get_comment_object( $this->impossible_id );

		$this->assertNull( $found_object );
	}
}
