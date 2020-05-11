<?php
namespace Mantle\Tests\Database\Model;

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
}
