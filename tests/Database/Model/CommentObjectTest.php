<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Comment;
use Mantle\Testing\FrameworkTestCase;

class CommentObjectTest extends FrameworkTestCase {
	public function test_comment_object(): void {
		$comment = static::factory()->comment->create_and_get();
		$object  = Comment::find_or_fail( $comment );

		$this->assertSame( $object->id(), (int) $comment->comment_ID );
		$this->assertSame( $object->name(), $comment->comment_author );
		$this->assertSame( $object->description(), $comment->comment_content );
		$this->assertNull( $object->parent() );

		// Retrieve an attribute straight from the object itself.
		$this->assertSame( $comment->comment_author_email, $object->get( 'comment_author_email' ) );

		// Test that you can get the WordPress object.
		$core_object = $object->core_object();
		$this->assertInstanceOf( \WP_Comment::class, $core_object );
		$this->assertSame( $object->id(), (int) $core_object->comment_ID );
	}

	public function test_comment_object_parent(): void {
		$parent_id  = static::factory()->comment->create();
		$comment_id = static::factory()->comment->create( [ 'comment_parent' => $parent_id ] );

		$this->assertIsInt( $comment_id );

		$object = Comment::find_or_fail( $comment_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Comment::class, $parent_object );
		$this->assertSame( $parent_object->id(), $parent_id );
	}

	public function test_comment_save_update(): void {
		$comment = static::factory()->comment->create_and_get();
		$object  = Comment::find_or_fail( $comment );

		$this->assertTrue( $object->save( [ 'comment_content' => 'Updated content' ] ) );

		$this->assertEquals( $comment->comment_ID, $object->id() );
		$updated = \get_comment( $comment->comment_ID );

		$this->assertInstanceOf( \WP_Comment::class, $updated );
		$this->assertEquals( 'Updated content', $updated->comment_content );

		// Saving again without any changes should not throw.
		$this->assertTrue( $object->save() );
		$this->assertEquals( $comment->comment_ID, $object->id() );
	}

	public function test_comment_meta(): void {
		$comment   = static::factory()->comment->create_and_get( [ 'taxonomy' => 'category' ] );
		$object = Comment::find_or_fail( $comment );

		$this->assertEmpty( \get_comment_meta( $object->id(), 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertSame( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertSame( 'meta_value_to_set', \get_comment_meta( $object->id(), 'meta_key_to_set', true ) );
	}
}
