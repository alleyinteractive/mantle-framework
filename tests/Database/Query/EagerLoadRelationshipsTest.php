<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Utils;

use function Mantle\Support\Helpers\collect;

class EagerLoadRelationshipsTest extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();
		Utils::delete_all_data();
		register_post_type( Another_Testable_Post_Eager::get_object_name() );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( Another_Testable_Post_Eager::get_object_name() );
	}

	public function test_relationship_as_attribute() {
		$post = Testable_Post_Eager::find( static::factory()->post->create() );
		$this->assertNull( $post->post_relationship );

		$another_post = $post->post_relationship()->save( new Another_Testable_Post_Eager(
			[
				'post_status' => 'publish',
				'post_title' => 'Another Testable Post',
			]
		) );

		// Add some posts after to make this random.
		static::factory()->post->create_many( 10 );
		static::factory()->post->create_many( 10, [ 'post_type' => 'example-post-eager' ] );

		$this->assertEquals( $another_post->id, $post->post_relationship->id );
		$this->assertEquals( $post->id, $another_post->post->id );
	}

	public function test_eager_loading_relationships_has_one() {
		Utils::delete_all_posts();

		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				function() use ( &$related_post_ids ) {
					$post = Testable_Post_Eager::find( static::factory()->post->create() );

					$related_post = $post->post_relationship()->save( new Another_Testable_Post_Eager(
						[
							'status' => 'publish',
							'title'  => "$post->title relation"
						]
					) );

					// Store the ID for testing later.
					$related_post_ids[ $post->id ] = $related_post->id;

					$this->assertEquals( $related_post->id, $post->post_relationship->id );

					return $post;
				}
			)
			->all();

		// Eager load the models.
		$posts = Testable_Post_Eager::with( 'post_relationship' )->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'post_relationship' ) );
			$this->assertNotNull( $post->post_relationship, 'Expecting that the "post_relationship" has an actual model' );
			$this->assertEquals( $related_post_ids[ $post->id ] ?? null, $post->post_relationship->id );
		}
	}

	public function test_eager_loading_relationships_has_many() {
		Utils::delete_all_posts();

		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				function() use ( &$related_post_ids ) {
					$post = Testable_Post_Eager::find( static::factory()->post->create() );

					for ( $i = 0; $i < 3; $i++ ) {
						$related_post = $post->posts_relationship()->save( new Another_Testable_Post_Eager(
							[
								'post_status' => 'publish',
								'post_title' => "$post->title relation"
							]
						) );

						// Store the ID for testing later.
						$related_post_ids[ $post->id ][] = $related_post->id;
					}

					return $post;
				}
			)
			->to_array();

		// Eager load the models.
		$posts = Testable_Post_Eager::with('posts_relationship')->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'posts_relationship' ) );
			$this->assertNotEmpty( $related_post_ids[ $post->id ] ?? [] );

			$ids = collect( $post->posts_relationship )->pluck( 'id' )->all();

			foreach ( $related_post_ids[ $post->id ] as $related_id ) {
				$this->assertTrue( in_array( $related_id, $ids, true ) );
			}
		}
	}

	public function test_eager_loading_relationships_belongs_to() {
		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				function() use ( &$related_post_ids ) {
					// $related_post = Another_Testable_Post_Eager::
					$related_post = Another_Testable_Post_Eager::find( static::factory()->post->create( [ 'post_type' => 'example-post-eager' ] ) );

					$post = $related_post->post()->save( new Testable_Post_Eager(
						[
							'post_status' => 'publish',
							'post_title' => "$related_post->title relation"
						]
					) );

					// Store the ID for testing later.
					$related_post_ids[ $related_post->id ] = $post->id;

					return $post;
				}
			)
			->to_array();

		// Eager load the models.
		$posts = Another_Testable_Post_Eager::with('post')->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'post' ) );
			$this->assertEquals( $related_post_ids[ $post->id ], $post->post->id );
		}
	}

	public function test_eager_loading_post_to_term() {
		$posts = [];
		$tags  = [];

		for ( $i = 0; $i < 10; $i++ ) {
			$post = $posts[] = Testable_Post_Eager::find( static::factory()->post->create() );

			$tags[ $post->id ] = $post->term_relationship()->save( new Testable_Tag_Eager( [
				'name' => "Tag {$i}",
			] ) );
		}

		$posts = Testable_Post_Eager::with( 'term_relationship' )->get();

		foreach ( $posts as $i => $post ) {
			$this->assertEquals( $posts[ $i ]->id, $post->id );

			$this->assertTrue( $post->relation_loaded( 'term_relationship' ) );
			$this->assertNotNull( $post->term_relationship );
			$this->assertEquals( $post->term_relationship[0]->id, $tags[ $post->id ]->id );
		}
	}

	public function test_eager_loading_term_to_post() {
		$tags  = [];
		$posts = [];

		for ( $i = 0; $i < 5; $i++ ) {
			$tag = $tags[] = Testable_Tag_Eager::find( static::factory()->tag->create() );

			for ( $n = 0; $n < rand( 2, 5 ); $n++ ) {
				$posts[ $tag->id ][] = $tag->posts()->save(
					new Testable_Post_Eager( [
						'title'  => "Tag Post {$n}",
						'status' => 'publish',
					] )
				);
			}
		}

		static::factory()->post->create_many( 10 );

		$tags = Testable_Tag_Eager::with( 'posts' )->all();

		$this->assertEquals( count( $tags ), count( $tags ) );

		foreach ( $tags as $i => $tag ) {
			$this->assertTrue( $tag->relation_loaded( 'posts' ) );
			$this->assertNotNull( $tag->posts );

			$tag_post_ids = collect( $tag->posts )->pluck( 'id' );

			$this->assertEquals( count( $tag_post_ids ), count( $tag->posts ) );

			foreach ( $posts[ $tag->id ] as $post ) {
				$this->assertNotFalse( $tag_post_ids->search( $post->id, true ) );
			}
		}
	}

	public function test_eager_loading_term_to_term_has_one() {
		$tags = [];
		$categories = [];

		for ( $i = 0; $i < 10; $i++ ) {
			$tag = $tags[] = Testable_Tag_Eager::find( static::factory()->tag->create() );

			for ( $n = 0; $n < rand( 2, 5 ); $n++ ) {
				$categories[ $tag->id() ][] = $tag->category()->save(
					new Testable_Category_Eager( [ 'name' => "Eager Category {$tag->id} {$n}" ] )
				);
			}
		}

		$results = Testable_Tag_Eager::with( 'category' )->all();

		$this->assertEquals( count( $tags ), count( $results ) );

		foreach ( $results as $result ) {
			$this->assertTrue( $result->relation_loaded( 'category' ) );
			$this->assertNotNull( $result->category );

			$tag_category_ids = collect( $result->category )->pluck( 'id' );

			foreach ( $categories[ $result->id ] as $category ) {
				$this->assertNotFalse( $tag_category_ids->search( $category->id(), true ) );
			}
		}
	}

	public function test_eager_loading_term_to_term_belongs_to() {
		$categories = [];
		$tags = [];

		for ( $i = 0; $i < 10; $i++ ) {
			$category = $categories[] = Testable_Category_Eager::create( [
				'name' => 'Testable Category Belongs To ' . $i,
			] );

			$tags[ $category->id() ] = $category->tag()->save(
				new Testable_Tag_Eager( [ 'name' => "Eager Tag {$category->id}" ] )
			);
		}

		static::factory()->tag->create_many( 10 );

		$results = Testable_Category_Eager::with( 'tag' )->all();

		foreach ( $results as $result ) {
			// Ignore thee default term.
			if ( 'uncategorized' === $result->slug ) {
				continue;
			}

			$this->assertTrue( $result->relation_loaded( 'tag' ) );
			$this->assertNotNull( $result->tag );
			$this->assertEquals( $tags[ $result->id ]->id, $result->tag->id );
		}
	}

	public function test_eager_loading_term_to_term_belongs_to_many() {
		$categories = [];
		$tags = [];

		$count = 0;

		for ( $i = 0; $i < 10; $i++ ) {
			$category = $categories[] = Testable_Category_Eager::create( [
				'name' => 'Testable Category Belongs To ' . $i,
			] );

			for ( $n = 0; $n < rand( 2, 5 ); $n++ ) {
				$count++;
				$tags[ $category->id() ][] = $category->tags()->save(
					new Testable_Tag_Eager( [ 'name' => "Eager Tag {$category->id} {$n}" ] )
				);
			}
		}

		static::factory()->tag->create_many( 10 );

		$results = Testable_Category_Eager::with( 'tags' )->all();

		foreach ( $results as $result ) {
			// Ignore thee default term.
			if ( 'uncategorized' === $result->slug ) {
				continue;
			}

			$this->assertTrue( $result->relation_loaded( 'tags' ) );
			$this->assertNotNull( $result->tags );

			$this->assertEquals( count( $tags[ $result->id ] ), count( $result->tags ) );

			// Assert that the eager loaded terms match the expected.
			$term_ids = wp_list_pluck( $tags[ $result->id ], 'id' );

			foreach ( $result->tags as $tag ) {
				$this->assertTrue( in_array( $tag->id, $term_ids, false ) );
			}
		}
	}
}

class Testable_Post_Eager extends Post {
	public static $object_name = 'post';

	public function post_relationship() {
		return $this->has_one( Another_Testable_Post_Eager::class );
	}

	public function posts_relationship() {
		return $this->has_many( Another_Testable_Post_Eager::class );
	}

	public function term_relationship() {
		return $this->has_many( Testable_Tag_Eager::class );
	}
}

class Another_Testable_Post_Eager extends Post {
	public static $object_name = 'example-post-eager';

	public function post() {
		return $this->belongs_to( Testable_Post_Eager::class );
	}
}

class Testable_Tag_Eager extends Term {
	public static $object_name = 'post_tag';

	public function category() {
		return $this->has_many( Testable_Category_Eager::class );
	}

	public function posts() {
		return $this->has_many( Testable_Post_Eager::class );
	}
}

class Testable_Category_Eager extends Testable_Tag_Eager {
	public static $object_name = 'category';

	public function tag() {
		return $this->belongs_to( Testable_Tag_Eager::class );
	}

	public function tags() {
		return $this->belongs_to_many( Testable_Tag_Eager::class );
	}
}
