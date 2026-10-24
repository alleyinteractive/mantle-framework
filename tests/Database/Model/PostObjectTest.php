<?php
namespace Mantle\Tests\Database\Model;

use Carbon\Carbon;
use Mantle\Contracts\Database\Registrable;
use Mantle\Database\Model\Attachment;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Registration\Register_Post_Type;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Utils;

class PostObjectTest extends FrameworkTestCase {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		Test_Post_Type::register_object();

		Utils::delete_all_posts();
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( Test_Post_Type::get_registration_name() );
	}

	public function test_post_object(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$this->assertSame( $post->ID, $object->id() );
		$this->assertSame( $post->post_title, $object->name() );
		$this->assertSame( $post->post_name, $object->slug() );
		$this->assertSame( $post->post_excerpt, $object->description() );
		$this->assertSame( $post->post_content, $object->get( 'content' ) );

		// Retrieve an attribute straight from the object itself.
		$this->assertSame( $post->post_modified, $object->get( 'post_modified' ) );
		$this->assertSame( $post->post_content, $object->get( 'post_content' ) );

		// Test magic methods work.
		$this->assertSame( $post->post_content, $object->post_content );
		$this->assertSame( $post->post_title, $object->name );

		// Test that you can get the WordPress object.
		$core_object = $object->core_object();
		$this->assertInstanceOf( \WP_Post::class, $core_object );
		$this->assertSame( $object->id(), $core_object->ID );
	}

	public function test_post_object_parent(): void {
		$parent_id = static::factory()->post->create();
		$post_id   = static::factory()->post->create( [ 'post_parent' => $parent_id ] );

		$this->assertIsInt( $post_id );

		$object = Testable_Post::find_or_fail( $post_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Testable_Post::class, $parent_object );
		$this->assertSame( $parent_object->id(), $parent_id );
	}

	public function test_post_meta(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$this->assertEmpty( \get_post_meta( $post->ID, 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertSame( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertSame( 'meta_value_to_set', \get_post_meta( $post->ID, 'meta_key_to_set', true ) );

		$object->delete_meta( 'meta_key_to_set' );
		$this->assertEmpty( $object->get_meta( 'meta_key_to_set' ) );
	}

	public function test_post_meta_isset_and_missing(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$this->assertFalse( isset( $object->meta['unknown_meta_key'] ) );
		$this->assertNull( $object->meta->unknown_meta_key );
		$this->assertSame( 'fallback', $object->meta->unknown_meta_key ?? 'fallback' );

		update_post_meta( $post->ID, 'empty_meta_key', '' );

		$this->assertTrue( isset( $object->meta['empty_meta_key'] ) );
		$this->assertSame( '', $object->meta->empty_meta_key );
	}

	public function test_find_with_empty_value_does_not_return_global_post(): void {
		$post = static::factory()->post->create_and_get();

		$GLOBALS['post'] = $post;

		$this->assertNull( Testable_Post::find( 0 ) );
		$this->assertNull( Testable_Post::find( null ) );
		$this->assertNull( Testable_Post::find( '' ) );

		$this->expectException( \Mantle\Database\Model\Model_Not_Found_Exception::class );

		Testable_Post::find_or_fail( 0 );
	}

	public function test_create_with_accessor_stores_raw_value(): void {
		$model = Testable_Post_With_Accessor::create( [ 'title' => 'Raw Title', 'status' => 'publish' ] );

		$this->assertSame( 'Prefixed Raw Title', $model->title );
		$this->assertSame( 'Raw Title', get_post_field( 'post_title', $model->id() ) );
	}

	public function test_post_meta_attributes(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$this->assertEmpty( \get_post_meta( $post->ID, 'attr_meta_key_to_set', true ) );

		$object->save(
			[
				'meta' => [
					'attr_meta_key_to_set' => 'attr_meta_value_to_set',
				],
			]
		);

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$meta_value = $object->get_meta( 'attr_meta_key_to_set' );
		$this->assertSame( 'attr_meta_value_to_set', $meta_value );
		$this->assertSame( 'attr_meta_value_to_set', \get_post_meta( $post->ID, 'attr_meta_key_to_set', true ) );

		$object->save(
			[
				'meta' => [
					'attr_meta_key_to_set' => '',
				],
			]
		);

		$this->assertEmpty( $object->get_meta( 'attr_meta_key_to_set' ) );
	}

	public function test_post_meta_attributes_invalid(): void {
		$this->expectException( Model_Exception::class );

		new Testable_Post(
			[
				'meta' => 'as-a-string'
			]
		);
	}

	public function test_schedule_published_post(): void {
		$post = Post::find_or_fail(
			static::factory()->post->create_and_get(
				[
					'post_date'   => Carbon::now()->subWeek()->toDateTimeString(),
					'post_status' => 'publish',
				]
			)
		);

		$date = Carbon::now()->addDay()->startOfMinute();

		$this->assertTrue( $post->schedule( $date ) );

		$core = get_post( $post->id() );

		$this->assertInstanceOf( \WP_Post::class, $core );
		$this->assertEquals( 'future', $core->post_status );
		$this->assertEquals( $date->toDateTimeString(), $core->post_date );
		$this->assertEquals( get_gmt_from_date( $core->post_date ), $core->post_date_gmt );
	}

	public function test_updating_post(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$object->post_content = 'Updated Content';
		$object->save();

		$this->assertSame( 'Updated Content', $object->post_content );

		$this->assertSame( 'Updated Content', get_post_field( 'post_content', $post->ID ) );
	}

	public function test_updating_with_alias(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$object->name = 'Updated Title';
		$object->save();

		$this->assertSame( 'Updated Title', $object->name );

		$this->assertSame( 'Updated Title', get_post_field( 'post_title', $post->ID ) );
	}

	public function test_updating_only_save_method(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$object->save(
			[
				'name' => 'Saved Through Attribute',
			]
		);

		$this->assertSame( 'Saved Through Attribute', $object->name );

		$this->assertSame( 'Saved Through Attribute', get_post_field( 'post_title', $post->ID ) );
	}

	public function test_delete_post(): void {
		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		// Test trashing a post.
		$object->delete( false );

		$this->assertSame( 'trash', get_post_status( $post->ID ) );

		// Delete the post for good.
		$object->delete( true );

		$this->assertEmpty( \get_post( $post->ID ) );
	}

	public function test_creating_post_through_model(): void {
		$post = new Post(
			[
				'post_title'   => 'Example Title',
				'post_content' => 'Example Content',
			]
		);

		$post->save();
		$this->assertNotEmpty( $post->id() );
	}

	public function test_setting_guarded_id(): void {
		$this->expectException( Model_Exception::class );

		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$object->id = 12345;
		$object->save();
	}

	public function test_setting_unguarded_attribute(): void {
		$this->expectException( Model_Exception::class );

		$post   = static::factory()->post->create_and_get();
		$object = Testable_Post::find_or_fail( $post );

		$object->id = 12345;
		$object->set_model_guard( false );
		$object->save();
	}

	public function test_model_post_type(): void {
		$post = static::factory()->post->create( [ 'post_type' => 'example-post-type' ] );

		$this->assertIsInt( $post );
		$object = Test_Post_Type::find( $post );
		$this->assertInstanceOf( Test_Post_Type::class, $object );
	}

	public function test_incorrect_post_type_for_model(): void {
		$post = static::factory()->post->create( [ 'post_type' => 'post' ] );

		$this->assertIsInt( $post );
		$object = Test_Post_Type::find( $post );
		$this->assertNull( $object );
	}

	public function test_post_type_assumed(): void {
		$object = new Test_Post_Type( [ 'name' => 'post-type-test' ] );
		$object->save();
		$this->assertNotEmpty( $object->id() );
		$this->assertSame( 'example-post-type', $object->get( 'post_type' ) );

	}

	public function test_model_incorrect_post_type(): void {
		register_post_type( 'example_post_type' );
		$post_id = static::factory()->post->create( [ 'post_type' => 'example_post_type' ] );

		$this->assertIsInt( $post_id );

		$this->assertNull( Testable_Post::find( $post_id ) );
	}

	public function test_dynamic_model_instance(): void {
		register_post_type( 'foo_post_type' );

		$post_id = static::factory()->post->for( 'foo_post_type' )->create();

		$this->assertIsInt( $post_id );
		$post_two = static::factory()->post->for( 'foo_post_type' )->create( [
			'post_title' => 'Post Two',
		] );

		$this->assertSame( 'foo_post_type', get_post_type( $post_id ) );

		$dynamic_model = Post::for( 'foo_post_type' );

		$this->assertEmpty( Post::find( $post_id ) );

		$found = $dynamic_model->find( $post_id );

		$this->assertInstanceOf( Post::class, $found );
		$this->assertSame( $post_id, $found->id() );

		$found_by_title = $dynamic_model->where( 'title', 'Post Two' )->first();

		$this->assertInstanceOf( Post::class, $found_by_title );
		$this->assertSame( $post_two, $found_by_title->id() );

		// Test creating a post via the dynamic model.
		$new_post = $dynamic_model->create(
			[
				'post_title' => 'New Post',
			]
		);

		$this->assertSame( 'foo_post_type', get_post_field( 'post_type', $new_post->id() ) );
		$this->assertSame( 'New Post', get_post_field( 'post_title', $new_post->id() ) );
	}

	public function test_query_builder(): void {
		$post_id = $this->get_random_post_id();
		$first = Testable_Post::where( 'id', $post_id )->first();

		$this->assertInstanceOf( Testable_Post::class, $first );

		$this->assertSame( $post_id, $first->id() );
	}

	/**
	 * Ensure that 'set_test_key_attribute()' method is used on the model.
	 */
	public function test_mutated_attribute(): void {
		$object = new Testable_Post( [ 'test_key' => 'non-mutated-attribute' ] );

		$this->assertSame( 'mutated_value', $object->get( 'test_key' ) );
	}

	public function test_setting_meta_before_saving(): void {
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
		$this->assertSame( 'meta_value_to_check', $object->get_meta( 'meta_key' ) );

		$object->meta->meta_key = 'Updated meta value';
		$this->assertSame( 'meta_value_to_check', $object->get_meta( 'meta_key' ) );

		// Retrieving by attribute should give the queued value.
		$this->assertSame( 'Updated meta value', $object->meta->meta_key );

		$object->save();
		$this->assertSame( 'Updated meta value', $object->get_meta( 'meta_key' ) );
		$this->assertSame( 'Updated meta value', $object->meta->meta_key );
	}

	public function test_meta_as_array(): void {
		$object = new Testable_Post(
			[
				'name' => 'Test Post with Meta',
			]
		);

		$object->meta['meta_key'] = 'meta_value_to_check';

		$object->save();

		$this->assertNotEmpty( $object->id() );
		$this->assertSame( 'meta_value_to_check', $object->get_meta( 'meta_key' ) );
		$this->assertSame( 'meta_value_to_check', $object->meta['meta_key'] );

		$object->meta['meta_key'] = 'Updated meta value';

		$object->save();

		$this->assertSame( 'Updated meta value', $object->get_meta( 'meta_key' ) );
	}

	public function test_terms_attribute(): void {
		$post = static::factory()->post->as_models()->create_and_get();
		$category = static::factory()->category->create_and_get();

		// Save the term to the post.
		$post->terms->category = [ $category ];
		$post->save();

		$this->assertPostHasTerm( $post, $category );

		$this->assertSame(
			$category->term_id,
			$post->terms->category[0]->id(),
		);

		// Remove the term.
		$post->terms->category = [];
		$post->save();

		$this->assertEmpty( get_the_category( $post->id() ) );
	}

	public function test_terms_attribute_create(): void {
		$category = static::factory()->category->create_and_get();

		// Create a post using terms attribute.
		$new_post = static::factory()->post->as_models()->create_and_get( [
			'terms' => [
				'category' => [ $category ],
			],
		] );

		$this->assertPostHasTerm( $new_post, $category );
	}

	public function test_terms_attribute_create_without_taxonomy(): void {
		$category_a = static::factory()->category->create_and_get();
		$category_b = static::factory()->category->create_and_get();

		// Create a post using terms attribute without specifying a taxonomy.
		$new_post = static::factory()->post->as_models()->create_and_get( [
			'terms' => [ $category_a, $category_b ],
		] );

		$this->assertCount( 2, get_the_category( $new_post->id() ) );
		$this->assertPostHasTerm( $new_post, $category_a );
		$this->assertPostHasTerm( $new_post, $category_b );
	}

	public function test_terms_attribute_create_without_taxonomy_multiple(): void {
		$category = static::factory()->category->create_and_get();
		$tag      = static::factory()->tag->create_and_get();

		// Create a post using terms attribute without specifying a taxonomy.
		$new_post = static::factory()->post->as_models()->create_and_get( [
			'terms' => [ $category, $tag ],
		] );

		$this->assertPostHasTerm( $new_post, $category );
		$this->assertPostHasTerm( $new_post, $tag );
	}

	public function test_get_all(): void {
		Post::all()->each( fn ( Post $post ) => $post->delete( true ) );

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

	public function test_modified_date(): void {
		$original = now()->subYear()->toDateTimeString();

		$post = static::factory()->post->as_models()->create_and_get( [
			'modified' => $original,
		] );

		$this->assertSame( $original, $post->modified );

		$date = now()->subWeek()->toDateTimeString();

		$post->modified = $date;
		$post->save();

		$this->assertSame( $date, $post->modified );

		// Test that an update to the post object will update the modified date.
		$post->save( [ 'content' => 'Updated Title' ] );

		$this->assertTrue( Carbon::parse( $post->modified )->gt( $date ) );
	}

	public function test_dates_attribute(): void {
		$post = static::factory()->post->as_models()->create_and_get();

		// Test that the dates are the same as the post object.
		$this->assertSame( $post->post_date, $post->dates->created->toDateTimeString() );
		$this->assertSame( $post->post_date_gmt, $post->dates->created_utc->toDateTimeString() );
		$this->assertSame( $post->post_modified, $post->dates->modified->toDateTimeString() );
		$this->assertSame( $post->post_modified_gmt, $post->dates->modified_utc->toDateTimeString() );

		$newDate = now()->subWeek();

		// Test updating the date.
		$post->dates->created = $newDate;
		$post->save();

		$this->assertSame( $newDate->toDateTimeString(), $post->dates->created->toDateTimeString() );

		// Test updating the date with as an array.
		$post->dates['modified'] = $newDate;
		$post->save();

		$this->assertSame( $newDate->toDateTimeString(), $post->dates->modified->toDateTimeString() );
	}

	public function test_thumbnail_relationship(): void {
		$post          = static::factory()->post->as_models()->create_and_get();

		$attachment_id = static::factory()->attachment->with_image()->create();

		$this->assertIsInt( $attachment_id );

		$post->thumbnail()->save( $attachment_id );

		$this->assertInstanceOf( Attachment::class, $post->thumbnail );
		$this->assertSame( $attachment_id, $post->thumbnail->id() );
		$this->assertSame( $attachment_id, get_post_thumbnail_id( $post->id() ) );

		$post->thumbnail()->dissociate();

		$this->assertEmpty( $post->thumbnail );
		$this->assertEmpty( get_post_thumbnail_id( $post->id() ) );
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @param array<string, mixed> $args Arguments for the posts.
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
	public function set_test_key_attribute(): void {
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
	 * @return array<mixed>
	 */
	public static function get_registration_args(): array {
		return [
			'public' => true,
		];
	}
}

class Testable_Post_With_Accessor extends Post {
	public static $object_name = 'post';

	public function get_post_title_attribute( string $value ): string {
		return 'Prefixed ' . $value;
	}
}
