<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Relations\Has_One;
use Mantle\Database\Model\Relations\Has_Many;
use Mantle\Database\Model\Relations\Belongs_To_Many;
use Mantle\Database\Model\Relations\Belongs_To;
use Mantle\Database\Model\Term;
use Mantle\Support\Collection;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Utils;

use function Mantle\Support\Helpers\collect;

class EagerLoadRelationshipsTest extends FrameworkTestCase {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();
		Utils::delete_all_data();
		$name = Another_Testable_Post_Eager::get_object_name();

		$this->assertNotEmpty( $name );

		register_post_type( strtolower( $name ) );
	}

	protected function tearDown(): void {
		parent::tearDown();
		$name = Another_Testable_Post_Eager::get_object_name();

		$this->assertNotNull( $name );

		unregister_post_type( $name );
	}

	public function test_relationship_as_attribute(): void {
		$post = Testable_Post_Eager::find_or_fail( static::factory()->post->create_and_get() );
		$relationship = $post->post_relationship;

		$this->assertNull( $relationship );

		$another_post = $post->post_relationship()->save( new Another_Testable_Post_Eager(
			[
				'post_status' => 'publish',
				'post_title' => 'Another Testable Post',
			]
		) );

		// Add some posts after to make this random.
		static::factory()->post->create_many( 10 );
		static::factory()->post->create_many( 10, [ 'post_type' => 'example-post-eager' ] );

		$relationship = $post->post_relationship;

		$this->assertInstanceOf( Another_Testable_Post_Eager::class, $relationship );
		$this->assertSame( $another_post->id, $relationship->id );
		$this->assertSame( $post->id, $another_post->post->id );
	}

	public function test_eager_loading_relationships_has_one(): void {
		Utils::delete_all_posts();

		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				function() use ( &$related_post_ids ) {
					$post = Testable_Post_Eager::find_or_fail( static::factory()->post->create_and_get() );

					$related_post = $post->post_relationship()->save( new Another_Testable_Post_Eager(
						[
							'status' => 'publish',
							'title'  => "$post->title relation"
						]
					) );

					// Store the ID for testing later.
					$related_post_ids[ $post->id ] = $related_post->id;

					$this->assertInstanceOf( Another_Testable_Post_Eager::class, $post->post_relationship );
					$this->assertSame( $related_post->id, $post->post_relationship->id );

					return $post;
				}
			)
			->all();

		// Eager load the models.
		$posts = Testable_Post_Eager::with( 'post_relationship' )->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'post_relationship' ) );
			$this->assertNotNull( $post->post_relationship, 'Expecting that the "post_relationship" has an actual model' );
			$this->assertSame( $related_post_ids[ $post->id ] ?? null, $post->post_relationship->id );
		}
	}

	public function test_relationship_query_is_not_limited(): void {
		$post = Testable_Post_Eager::find_or_fail( static::factory()->post->create_and_get() );

		$this->assertSame( -1, $post->posts_relationship()->get_query()->get_query_args()['posts_per_page'] );
	}

	public function test_eager_loading_relationships_has_many(): void {
		Utils::delete_all_posts();

		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				static function() use ( &$related_post_ids ) {
					$post = Testable_Post_Eager::find_or_fail( static::factory()->post->create_and_get() );

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
				$this->assertContains( $related_id, $ids );
			}
		}
	}

	public function test_eager_loading_relationships_belongs_to(): void {
		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				static function() use ( &$related_post_ids ) {
					// $related_post = Another_Testable_Post_Eager::
					$related_post = Another_Testable_Post_Eager::find_or_fail( static::factory()->post->create_and_get( [ 'post_type' => 'example-post-eager' ] ) );

					$post = $related_post->post()->save( new Testable_Post_Eager(
						[
							'post_status' => 'publish',
							'post_title' => "$related_post->title relation"
						]
					) );

					// Store the ID for testing later.
					$related_post_ids[ $related_post->id ] = $post->get( 'id' );

					return $post;
				}
			)
			->to_array();

		// Eager load the models.
		$posts = Another_Testable_Post_Eager::with('post')->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'post' ) );
			$this->assertSame( $related_post_ids[ $post->id ], $post->post->id );
		}
	}

	public function test_eager_loading_post_to_term(): void {
		$posts = [];
		$tags  = [];

		for ( $i = 0; $i < 10; $i++ ) {
			$post = $posts[] = Testable_Post_Eager::find_or_fail( static::factory()->post->create_and_get() );

			$tags[ $post->id ] = $post->term_relationship()->save( new Testable_Tag_Eager( [
				'name' => "Tag {$i}",
			] ) );
		}

		$posts = Testable_Post_Eager::with( 'term_relationship' )->get();

		foreach ( $posts as $i => $post ) {
			$this->assertSame( $posts->all()[ $i ]->id, $post->id );

			$this->assertTrue( $post->relation_loaded( 'term_relationship' ) );
			$this->assertNotNull( $post->term_relationship );
			$this->assertSame( $post->term_relationship[0]->id, $tags[ $post->id ]->id );
		}
	}

	public function test_eager_loading_term_to_post(): void {
		$tags  = [];
		$posts = [];

		for ( $i = 0; $i < 5; $i++ ) {
			$tag = $tags[] = Testable_Tag_Eager::find_or_fail( static::factory()->tag->create_and_get() );

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

		$this->assertCount( count( $tags ), $tags );

		foreach ( $tags as $i => $tag ) {
			$this->assertTrue( $tag->relation_loaded( 'posts' ) );
			$this->assertNotNull( $tag->posts );

			$tag_post_ids = collect( $tag->posts )->pluck( 'id' );

			$this->assertCount( count( $tag_post_ids ), $tag->posts );

			foreach ( $posts[ $tag->id ] as $post ) {
				$this->assertNotFalse( $tag_post_ids->search( $post->id, true ) );
			}
		}
	}

	public function test_eager_loading_term_to_term_has_one(): void {
		$tags = [];
		$categories = [];

		for ( $i = 0; $i < 10; $i++ ) {
			$tag = $tags[] = Testable_Tag_Eager::find_or_fail( static::factory()->tag->create_and_get() );

			for ( $n = 0; $n < rand( 2, 5 ); $n++ ) {
				$categories[ $tag->id() ][] = $tag->category()->save(
					new Testable_Category_Eager( [ 'name' => "Eager Category {$tag->id} {$n}" ] )
				);
			}
		}

		$results = Testable_Tag_Eager::with( 'category' )->all();

		$this->assertCount( count( $tags ), $results );

		foreach ( $results as $result ) {
			$this->assertTrue( $result->relation_loaded( 'category' ) );
			$this->assertNotNull( $result->category );

			$tag_category_ids = collect( $result->category )->pluck( 'id' );

			foreach ( $categories[ $result->id ] as $category ) {
				$this->assertNotFalse( $tag_category_ids->search( $category->id(), true ) );
			}
		}
	}

	public function test_eager_loading_term_to_term_belongs_to(): void {
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
			$this->assertSame( $tags[ $result->id ]->get( 'id' ), $result->tag->id );
		}
	}

	public function test_eager_loading_term_to_term_belongs_to_many(): void {
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

			$this->assertCount( count( $tags[ $result->id ] ), $result->tags );

			// Assert that the eager loaded terms match the expected.
			$term_ids = wp_list_pluck( $tags[ $result->id ], 'id' );

			foreach ( $result->tags as $tag ) {
				$this->assertContainsEquals( $tag->id, $term_ids );
			}
		}
	}
}

/**
 * @property Another_Testable_Post_Eager|null $post_relationship
 * @property Collection<int, Another_Testable_Post_Eager> $posts_relationship
 * @property array<int, Testable_Tag_Eager>|null $term_relationship
 */
class Testable_Post_Eager extends Post {
	public static $object_name = 'post';

	/**
	 * @return Has_One<static, Another_Testable_Post_Eager>
	 */
	public function post_relationship(): Has_One {
		return $this->has_one( Another_Testable_Post_Eager::class );
	}

	/**
	 * @return Has_Many<static, Another_Testable_Post_Eager>
	 */
	public function posts_relationship(): Has_Many {
		return $this->has_many( Another_Testable_Post_Eager::class );
	}

	/**
	 * @return Has_Many<static, Testable_Tag_Eager>
	 */
	public function term_relationship(): Has_Many {
		return $this->has_many( Testable_Tag_Eager::class );
	}
}

/**
 * @property Testable_Post_Eager $post
 */
class Another_Testable_Post_Eager extends Post {
	public static $object_name = 'example-post-eager';

	/**
	 * @return Belongs_To<static, Testable_Post_Eager>
	 */
	public function post(): Belongs_To {
		return $this->belongs_to( Testable_Post_Eager::class );
	}
}

/**
 * @property Collection<int, Testable_Category_Eager>|null $category
 * @property Collection<int, Testable_Post_Eager>|null $posts
 */
class Testable_Tag_Eager extends Term {
	public static $object_name = 'post_tag';

	/**
	 * @return Has_Many<static, Testable_Category_Eager>
	 */
	public function category(): Has_Many {
		return $this->has_many( Testable_Category_Eager::class );
	}

	/**
	 * @return Has_Many<static, Testable_Post_Eager>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post_Eager::class );
	}
}

/**
 * @property Testable_Tag_Eager|null $tag
 * @property Collection<int, Testable_Tag_Eager>|null $tags
 */
class Testable_Category_Eager extends Testable_Tag_Eager {
	public static $object_name = 'category';

	/**
	 * @return Belongs_To<static, Testable_Tag_Eager>
	 */
	public function tag(): Belongs_To {
		return $this->belongs_to( Testable_Tag_Eager::class );
	}

	/**
	 * @return Belongs_To_Many<static, Testable_Tag_Eager>
	 */
	public function tags(): Belongs_To_Many {
		return $this->belongs_to_many( Testable_Tag_Eager::class );
	}
}
