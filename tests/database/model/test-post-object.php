<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Post;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Post_Object extends WP_UnitTestCase {
	public function test_post_object() {
		$post   = $this->factory->post->create_and_get();
		$object = Post::find( $post );

		$this->assertInstanceOf( Post::class, $object );

		$this->assertEquals( $post->ID, $object->id() );
		$this->assertEquals( $post->post_title, $object->name() );
		$this->assertEquals( $post->post_name, $object->slug() );
		$this->assertEquals( $post->post_excerpt, $object->description() );
		$this->assertEquals( $post->post_content, $object->get( 'content' ) );

		// Retrieve an attribute straight from the object itself.
		$this->assertEquals( $post->post_modified, $object->get( 'post_modified' ) );
		$this->assertEquals( $post->post_content, $object->get( 'post_content' ) );

		// Test magic methods work.
		$this->assertEquals( $post->post_content, $object->post_content );
		$this->assertEquals( $post->post_title, $object->name );
	}

	public function test_post_object_parent() {
		$parent_id = $this->factory->post->create();
		$post_id   = $this->factory->post->create( [ 'post_parent' => $parent_id ] );

		$object = Post::find( $post_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Post::class, $parent_object );
		$this->assertEquals( $parent_object->id(), $parent_id );
	}

	public function test_post_meta() {
		$post   = $this->factory->post->create_and_get();
		$object = Post::find( $post );

		$this->assertEmpty( \get_post_meta( $post->ID, 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertEquals( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertEquals( 'meta_value_to_set', \get_post_meta( $post->ID, 'meta_key_to_set', true ) );

		$object->delete_meta( 'meta_key_to_set' );
		$this->assertEmpty( $object->get_meta( 'meta_key_to_set' ) );
	}

	public function test_updating_post() {
		$post   = $this->factory->post->create_and_get();
		$object = Post::find( $post );

		$object->post_content = 'Updated Content';
		$object->save();

		$this->assertEquals( 'Updated Content', $object->post_content );

		$post = \get_post( $post->ID );
		$this->assertEquals( 'Updated Content', $post->post_content );
	}

	public function test_updating_with_alias() {
		$post   = $this->factory->post->create_and_get();
		$object = Post::find( $post );

		$object->name = 'Updated Title';
		$object->save();

		$this->assertEquals( 'Updated Title', $object->name );

		$post = \get_post( $post->ID );
		$this->assertEquals( 'Updated Title', $post->post_title );
	}

	public function test_updating_only_save_method() {
		$post   = $this->factory->post->create_and_get();
		$object = Post::find( $post );

		$object->save(
			[
				'name' => 'Saved Through Attribute',
			]
		);

		$this->assertEquals( 'Saved Through Attribute', $object->name );

		$post = \get_post( $post->ID );
		$this->assertEquals( 'Saved Through Attribute', $post->post_title );
	}

	public function test_delete_post() {
		$post   = $this->factory->post->create_and_get();
		$object = Post::find( $post );

		$this->assertInstanceOf( Post::class, $object );

		// Test trashing a post.
		$object->delete( false );

		$post = \get_post( $post->ID );
		$this->assertInstanceOf( Post::class, $object );
		$this->assertEquals( 'trash', $post->post_status );

		// Delete the post for good.
		$object->delete( true );

		$post = \get_post( $post->ID );

		$this->assertEmpty( $post );
	}

	public function test_setting_id() {
		$this->expectException( Model_Exception::class );

		$post   = $this->factory->post->create_and_get();
		$object = Post::find( $post );

		$object->id = 12345;
		$object->save();
	}
}
