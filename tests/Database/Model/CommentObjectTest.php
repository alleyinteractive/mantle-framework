<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Comment;
use Mantle\Testing\FrameworkTestCase;


class CommentObjectTest extends FrameworkTestCase {
	public function test_comment_object() {
		$comment = $this->factory->comment->create_and_get();
		$object  = Comment::find( $comment );

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

	public function test_comment_object_parent() {
		$parent_id  = $this->factory->comment->create();
		$comment_id = $this->factory->comment->create( [ 'comment_parent' => $parent_id ] );

		$object = Comment::find( $comment_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Comment::class, $parent_object );
		$this->assertSame( $parent_object->id(), $parent_id );
	}

	public function test_comment_save_update() {
		$comment = $this->factory->comment->create_and_get();
		$object  = Comment::find( $comment );

		$this->assertTrue( $object->save( [ 'comment_content' => 'Updated content' ] ) );

		$this->assertEquals( $comment->comment_ID, $object->id() );
		$this->assertEquals( 'Updated content', \get_comment( $comment->comment_ID )->comment_content );

		// Saving again without any changes should not throw.
		$this->assertTrue( $object->save() );
		$this->assertEquals( $comment->comment_ID, $object->id() );
	}

	public function test_comment_meta() {
		$comment   = $this->factory->comment->create_and_get( [ 'taxonomy' => 'category' ] );
		$object = Comment::find( $comment );

		$this->assertEmpty( \get_comment_meta( $comment->comment_ID, 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertSame( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertSame( 'meta_value_to_set', \get_comment_meta( $comment->comment_ID, 'meta_key_to_set', true ) );
	}
}
