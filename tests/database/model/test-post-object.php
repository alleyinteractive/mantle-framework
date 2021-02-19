<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Registration\Register_Post_Type;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;


class Test_Post_Object extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();
		Test_Post_Type::register_object();
	}

	protected function tearDown(): void {
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

		// Test that you can get the WordPress object.
		$core_object = $object->core_object();
		$this->assertInstanceOf( \WP_Post::class, $core_object );
		$this->assertEquals( $object->id(), $core_object->ID );
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

	public function test_post_meta_attributes() {
		$post   = $this->factory->post->create_and_get();
		$object = Testable_Post::find( $post );

		$this->assertEmpty( \get_post_meta( $post->ID, 'attr_meta_key_to_set', true ) );

		$object->save(
			[
				'meta' => [
					'attr_meta_key_to_set' => 'attr_meta_value_to_set',
				],
			]
		);

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertEquals( 'attr_meta_value_to_set', $object->get_meta( 'attr_meta_key_to_set' ) );
		$this->assertEquals( 'attr_meta_value_to_set', \get_post_meta( $post->ID, 'attr_meta_key_to_set', true ) );

		$object->save(
			[
				'meta' => [
					'attr_meta_key_to_set' => '',
				],
			]
		);

		$this->assertEmpty( $object->get_meta( 'attr_meta_key_to_set' ) );
	}

	public function test_post_meta_attributes_invalid() {
		$this->expectException( Model_Exception::class );

		new Testable_Post(
			[
				'meta' => 'as-a-string'
			]
		);
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
	 * Ensure that 'set_test_key_attribute()' method is used on the model.
	 */
	public function test_mutated_attribute() {
		$object = new Testable_Post(
			[
				'test_key' => 'non-mutated-attribute',
			]
		);

		$this->assertEquals( 'mutated_value', $object->get( 'test_key' ) );
	}

	public function test_setting_meta_before_saving() {
		$object = new Testable_Post(
			[
				'name' => 'Test Post with Meta',
				'meta' => [
					'meta_key' => 'meta_value_to_check',
				],
			]
		);

		$object->save();
		$this->assertNotEmpty( $object->id() );
		$this->assertEquals( 'meta_value_to_check', $object->get_meta( 'meta_key' ) );

		$object->meta->meta_key = 'Updated meta value';
		$this->assertEquals( 'meta_value_to_check', $object->get_meta( 'meta_key' ) );

		// Retrieving by attribute should give the queued value.
		$this->assertEquals( 'Updated meta value', $object->meta->meta_key );

		$object->save();
		$this->assertEquals( 'Updated meta value', $object->get_meta( 'meta_key' ) );
		$this->assertEquals( 'Updated meta value', $object->meta->meta_key );
	}

	public function test_get_all() {
		Post::all()->each->delete( true );

		$published_post_ids = static::factory()->post->create_many( 5, [ 'post_status' => 'publish' ] );
		$draft_post_ids     = static::factory()->post->create_many( 5, [ 'post_status' => 'draft' ] );

		$all = Post::all()->pluck( 'id' );

		$this->assertCount( 5, $all );

		foreach ( $published_post_ids as $post_id ) {
			$this->assertTrue( false !== $all->search( $post_id, true ) );
		}

		// Ensure drafts aren't included.
		foreach ( $draft_post_ids as $post_id ) {
			$this->assertFalse( $all->search( $post_id, true ) );
		}

		// Query all with draft posts.
		$all = Post::anyStatus()->all()->pluck( 'id' );
		$this->assertCount( 10, $all );

		foreach ( $draft_post_ids as $post_id ) {
			$this->assertTrue( false !== $all->search( $post_id, true ) );
		}
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

	/**
	 * Allow testing the 'test_key' attribute.
	 */
	public function set_test_key_attribute() {
		$this->attributes['test_key'] = 'mutated_value';
	}
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
