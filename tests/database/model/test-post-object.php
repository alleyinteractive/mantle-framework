<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Post_Object extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		Test_Post_Type::register_post_type();
	}

	public function tearDown() {
		parent::tearDown();
		unregister_post_type( Test_Post_Type::get_object_name() );
	}

	public function test_post_object() {
		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

		$this->assertInstanceOf( Testable_Post::class, $object );

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

		$object = Testable_Post::find( $post_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Testable_Post::class, $parent_object );
		$this->assertEquals( $parent_object->id(), $parent_id );
	}

	public function test_post_meta() {
		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

		$this->assertEmpty( \get_post_meta( $post->ID, 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertEquals( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertEquals( 'meta_value_to_set', \get_post_meta( $post->ID, 'meta_key_to_set', true ) );

		$object->delete_meta( 'meta_key_to_set' );
		$this->assertEmpty( $object->get_meta( 'meta_key_to_set' ) );
	}

	public function test_updating_post() {
		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

		$object->post_content = 'Updated Content';
		$object->save();

		$this->assertEquals( 'Updated Content', $object->post_content );

		$post = \get_post( $post->ID );
		$this->assertEquals( 'Updated Content', $post->post_content );
	}

	public function test_updating_with_alias() {
		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

		$object->name = 'Updated Title';
		$object->save();

		$this->assertEquals( 'Updated Title', $object->name );

		$post = \get_post( $post->ID );
		$this->assertEquals( 'Updated Title', $post->post_title );
	}

	public function test_updating_only_save_method() {
		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

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
		$object = Testable_Post::find( $post );

		$this->assertInstanceOf( Testable_Post::class, $object );

		// Test trashing a post.
		$object->delete( false );

		$post = \get_post( $post->ID );
		$this->assertInstanceOf( Testable_Post::class, $object );
		$this->assertEquals( 'trash', $post->post_status );

		// Delete the post for good.
		$object->delete( true );

		$post = \get_post( $post->ID );

		$this->assertEmpty( $post );
	}

	public function test_creating_post_through_model() {
		$post = new Post(
			[
				'post_title'   => 'Example Title',
				'post_content' => 'Example Content',
			]
		);

		$post->save();
		$this->assertNotEmpty( $post->id() );
	}

	public function test_setting_guarded_id() {
		$this->expectException( Model_Exception::class );

		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

		$object->id = 12345;
		$object->save();
	}

	public function test_setting_unguarded_attribute() {
		$this->expectException( Model_Exception::class );

		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

		$object->id = 12345;
		$object->set_model_guard( false );
		$object->save();
	}

	public function test_model_post_type() {
		$post = $this->factory->post->create( [ 'post_type' => 'example-post-type' ] );
		$object = Test_Post_Type::find( $post );
		$this->assertInstanceOf( Test_Post_Type::class, $object );
	}

	public function test_incorrect_post_type_for_model() {
		$post = $this->factory->post->create( [ 'post_type' => 'post' ] );
		$object = Test_Post_Type::find( $post );
		$this->assertNull( $object );
	}

	public function test_post_type_assumed() {
		$object = new Test_Post_Type( [ 'name' => 'post-type-test' ] );
		$object->save();
		$this->assertNotEmpty( $object->id() );
		$this->assertEquals( 'example-post-type', $object->get( 'post_type' ) );

	}

	public function test_model_incorrect_post_type() {
		register_post_type( 'example_post_type' );
		$post_id = $this->factory->post->create( [ 'post_type' => 'example_post_type' ] );

		$this->assertNull( Testable_Post::find( $post_id ) );
	}

	public function test_query_builder() {
		$post_id = $this->get_random_post_id();
		$first = Testable_Post::whereId( $post_id )->first();
		$this->assertEquals( $post_id, $first->id() );
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @return integer
	 */
	protected function get_random_post_id( $args = [] ): int {
		$post_ids = static::factory()->post->create_many( 11, $args );
		array_pop( $post_ids );
		return $post_ids[ array_rand( $post_ids ) ];
	}
}

class Testable_Post extends Post {
	public static $object_name = 'post';
}

class Test_Post_Type extends Post implements Registrable {
	use Register_Post_Type;

	public static $object_name = 'example-post-type';

	/**
	 * Registration name for the model (post type, taxonomy name, etc.)
	 *
	 * @return string
	 */
	public static function get_registration_name(): string {
		return 'example-post-type';
	}

	/**
	 * Arguments to register the model with.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return [
			'public' => true,
		];
	}
}
