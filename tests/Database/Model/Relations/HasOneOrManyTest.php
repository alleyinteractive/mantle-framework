<?php
namespace Mantle\Tests\Database\Model\Relations\Has_One;

use Carbon\Carbon;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Relations\Has_Many;
use Mantle\Database\Model\Relations\Belongs_To_Many;
use Mantle\Database\Model\Relations\Belongs_To;
use Mantle\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Database\Model\Relations\Has_One;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Database\Model\Term;
use Mantle\Database\Model_Service_Provider;
use Mantle\Support\Collection;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Utils;

class HasOneOrManyTest extends FrameworkTestCase {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		register_post_type( 'sponsor' );
		register_taxonomy( 'test_taxonomy', 'post' );

		if ( ! taxonomy_exists( Has_One::RELATION_TAXONOMY ) ) {
			Model_Service_Provider::register_internal_taxonomy();
		}
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( 'sponsor' );
		unregister_taxonomy( 'test_taxonomy' );
	}

	public function test_has_one_post_to_post(): void {
		$date = Carbon::now()->subWeek()->toDateTimeString();

		$post = Testable_Post::create( [ 'title' => 'Test Post', 'date' => $date, 'status' => 'publish' ] );
		$page = $post->page()->save(
			new Testable_Page( [
				'date'   => $date,
				'status' => 'publish',
				'title'  => 'Test Page',
			] )
		);

		static::factory()->post->create_many( 10 );
		static::factory()->page->create_many( 10 );

		$this->assertSame( $page->id, $post->page->id );

		$first_page = $post->page()->first();

		$this->assertInstanceOf( Testable_Page::class, $first_page );
		$this->assertSame( $page->id, $first_page->id );

		// Check the inverse of the relationship (belongs to).
		$this->assertSame( $post->id, $page->post->id );
	}

	public function test_has_many_post_to_post(): void {
		Utils::delete_all_data();

		$date = Carbon::now()->subWeek();

		$post  = Testable_Post::create( [ 'title' => 'Test Post Many', 'date' => $date->toDateTimeString(), 'status' => 'publish' ] );
		$pages = [];

		for ( $i = 0; $i < 5; $i++ ) {
			$pages[] = $post->pages()->save(
				new Testable_Page( [
					'date'   => $date->subMinutes( $i )->toDateTimeString(),
					'status' => 'publish',
					'title'  => "Page {$i}",
				] )
			);
		}

		static::factory()->page->create_many( 10 );

		$this->assertCount( count( $pages ), $post->pages );

		// Assert that all pages exist and in the expected order.
		foreach ( $pages as $i => $page ) {
			$this->assertSame( $page->id, $post->pages->all()[ $i ]->id );

			// Check the inverse of the relationship (belongs to).
			$this->assertSame( $post->id, $page->post->id );
		}
	}

	public function has_many_post_to_post_saving(): void {
		$post    = Testable_Post::find_or_fail( $this->get_random_post_id() );
		$sponsor = Testable_Sponsor::find_or_fail( $this->get_random_post_id( [ 'post_type' => 'sponsor' ] ) );

		// Save the post to the sponsor.
		$sponsor->posts()->save( $post );

		// Query the sponsor's post.
		$sponsor_post = $sponsor->posts()->first();

		$this->assertInstanceOf( Testable_Post::class, $sponsor_post );
		$this->assertSame( $post->id(), $sponsor_post->id() );

		// Remove the sponsor from the post.
		$sponsor->posts()->remove( $post );

		// Query the relationship.
		$this->assertNull( $sponsor->posts()->first() );
	}

	public function test_has_many_post_to_post_is_not_limited(): void {
		$post = Testable_Post::create( [ 'title' => 'Parent with many pages', 'status' => 'publish' ] );

		for ( $i = 0; $i < 105; $i++ ) {
			$post->pages()->save( new Testable_Page( [ 'status' => 'publish', 'title' => "Page {$i}" ] ) );
		}

		$this->assertCount( 105, $post->pages );
		$this->assertCount( 105, $post->pages()->get() );
	}

	public function test_has_many_post_to_post_modifier(): void {
		$posts = array_map(
			static fn ( int $post_id ) => Testable_Post::find_or_fail( $post_id ),
			static::factory()->post->create_ordered_set( 50 ),
		);

		$sponsor = Testable_Sponsor::find_or_fail( $this->get_random_post_id( [ 'post_type' => 'sponsor' ] ) );

		// Save the posts to the sponsor.
		$sponsor->posts()->save( $posts );

		$this->assertCount( 50, $sponsor->posts()->get() );
		$this->assertCount( 10, $sponsor->posts_query_modifier()->get() );
	}

	public function test_has_many_post_to_post_modifier_using_terms(): void {
		$this->assertTrue( taxonomy_exists( Relation::RELATION_TAXONOMY ) );

		$posts = array_map(
			static fn ( int $post_id ) => Testable_Post_Using_Term::find_or_fail( $post_id ),
			static::factory()->post->create_ordered_set( 50 ),
		);

		$sponsor = Testable_Sponsor_Using_Term::find_or_fail( $this->get_random_post_id( [ 'post_type' => 'sponsor' ] ) );

		// Save the posts to the sponsor.
		$sponsor->posts()->save( $posts );

		$this->assertCount( 50, $sponsor->posts()->get() );
		$this->assertCount( 10, $sponsor->posts_query_modifier()->get() );
	}

	public function test_has_one_post_to_term(): void {
		$post = Testable_Post::find_or_fail( $this->get_random_post_id() );

		$tag = Testable_Tag::create(
			[
				'name' => 'Test Term Belongs To',
			]
		);

		$tag->posts()->save( $post );

		static::factory()->post->create_many( 10 );
		static::factory()->tag->create_many( 10 );

		$terms = get_the_tags( $post->id() );

		$this->assertIsArray( $terms );
		$this->assertNotEmpty( $terms );
		$this->assertSame( $tag->id(), array_shift( $terms )->term_id );

		// Check the relationship.
		$this->assertSame( $tag->id, $post->tag->id );

		$tag->posts()->remove( $post );
		$this->assertEmpty( get_the_terms( $post->id(), 'test_taxonomy' ) );
	}

	public function test_has_many_post_to_term(): void {
		$post = Testable_Post::find_or_fail( $this->get_random_post_id() );
		$tags = [];

		for ( $i = 0; $i < 5; $i++ ) {
			$tags[] = $post->tags()->save( new Testable_Tag( [
				'name' => "Tag {$i}",
			] ) );
		}

		static::factory()->tag->create_many( 10 );

		$post_tags = get_the_tags( $post->id() );

		$this->assertIsArray( $post_tags );
		$this->assertCount( count( $tags ), $post_tags );
		$this->assertCount( count( $tags ), $post->tags );

		// Check they match the expected tags.
		foreach ( $tags as $i => $tag ) {
			$this->assertSame( $tag->id, $post->tags->all()[ $i ]->id );
		}
	}

	public function test_has_one_term_to_post(): void {
		$tag = Testable_Tag::create( [ 'name' => 'Has One Term to Post' ] );

		$post = $tag->post()->save( new Testable_Post( [
			'date'   => Carbon::now()->subWeek()->toDateTimeString(),
			'name'   => 'Testable Post',
			'status' => 'publish',
		] ) );

		$this->assertSame( $post->id, $tag->post->id );

		$post_tags = get_the_tags( $post->id() );

		$this->assertIsArray( $post_tags );
		$this->assertSame( $tag->id, $post_tags[0]->term_id );

		$tag->post()->remove( $post );

		$this->assertEmpty( get_the_tags( $post->id() ) );
	}

	public function test_has_many_term_to_post(): void {
		$tag = Testable_Tag::create( [ 'name' => 'Has Many Term to Post' ] );

		$posts = [];

		$date = Carbon::now()->subWeek();

		for ( $i = 0; $i < 5; $i++ ) {
			$posts[] = $tag->posts()->save( new Testable_Post( [
				'date'   => $date->subMinutes( $i )->toDateTimeString(),
				'name'   => "Testable Post {$i}",
				'status' => 'publish',
			] ) );
		}

		$this->assertCount( count( $posts ), $tag->posts );

		foreach ( $posts as $i => $post ) {
			$this->assertTrue( has_tag( $tag->id, $post->id() ) );
			$this->assertSame( $post->id, $tag->posts->all()[ $i ]->id );
		}
	}

	public function test_has_many_post_to_post_with_terms(): void {
		$date = Carbon::now()->subDays( 7 )->toDateTimeString();
		$sponsor = Testable_Sponsor_Using_Term::create( [ 'title' => 'Sponsor Test', 'status' => 'publish', 'date' => $date ] );
		$post    = $sponsor->post()->save(
			new Testable_Post_Using_Term(
				[
					'date'   => $date,
					'status' => 'publish',
					'title'  => 'Post with Sponsor',
				]
			)
		);

		// Create some posts after the current.
		static::factory()->post->create_many( 10 );
		static::factory()->post->create_many( 10, [ 'post_type' => 'sponsor' ] );

		// Ensure it wasn't set using the post meta.
		$this->assertEmpty( $post->get_meta( 'testable_sponsor_using_term_id' ) );
		$this->assertNotEmpty( get_the_terms( $post->id(), Relation::RELATION_TAXONOMY ) );

		// Fetch the post through the relationship on the sponsor.
		$sponsors_post = $sponsor->post()->first();

		$this->assertInstanceOf( Testable_Post_Using_Term::class, $sponsors_post );
		$this->assertSame( $post->id, $sponsors_post->id );

		// Delete the relationship and ensure the internal term is removed.
		$sponsor->post()->remove( $post );
		$this->assertEmpty( get_the_terms( $post->id(), Relation::RELATION_TAXONOMY ) );
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @param array<string, mixed> $args Arguments for the posts.
	 * @return int
	 */
	protected function get_random_post_id( $args = [] ): int {
		$post_ids = static::factory()->post->create_many( 11, $args );
		array_pop( $post_ids );
		return $post_ids[ array_rand( $post_ids ) ];
	}
}

/**
 * @property Testable_Page $page
 * @property Collection<int, Testable_Page> $pages
 * @property Testable_Tag $tag
 * @property Collection<int, Testable_Tag> $tags
 */
class Testable_Post extends Post {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'post';

	/**
	 * @return Has_One<static, Testable_Page>
	 */
	public function page(): Has_One {
		return $this->has_one( Testable_Page::class );
	}

	/**
	 * @return Has_Many<static, Testable_Page>
	 */
	public function pages(): Has_Many {
		return $this->has_many( Testable_Page::class );
	}

	/**
	 * @return Has_One<static, Testable_Tag>
	 */
	public function tag(): Has_One {
		return $this->has_one( Testable_Tag::class );
	}

	/**
	 * @return Has_Many<static, Testable_Tag>
	 */
	public function tags(): Has_Many {
		return $this->has_many( Testable_Tag::class );
	}

	/**
	 * @return Belongs_To<static, Testable_Sponsor>
	 */
	public function sponsor(): Belongs_To {
		return $this->belongs_to( Testable_Sponsor::class );
	}

	/**
	 * @return Has_Many<static, Testable_Term>
	 */
	public function terms(): Has_Many {
		return $this->has_many( Testable_Term::class );
	}
}

/**
 * @property Testable_Post $post
 */
class Testable_Page extends Post {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'page';

	/**
	 * @return Belongs_To<static, Testable_Post>
	 */
	public function post(): Belongs_To {
		return $this->belongs_to( Testable_Post::class );
	}
}

/**
 * @property Testable_Post $post
 * @property Collection<int, Testable_Post> $posts
 */
class Testable_Tag extends Term {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'post_tag';

	/**
	 * @return Has_One<static, Testable_Post>
	 */
	public function post(): Has_One {
		return $this->has_one( Testable_Post::class );
	}

	/**
	 * @return Has_Many<static, Testable_Post>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Sponsor extends Post {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'sponsor';

	/**
	 * @return Has_One<static, Testable_Post>
	 */
	public function post(): Has_One {
		return $this->has_one( Testable_Post::class );
	}

	/**
	 * @return Has_Many<static, Testable_Post>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post::class );
	}

	/**
	 * @return Has_Many<static, Testable_Post>
	 */
	public function posts_query_modifier(): Has_Many {
		$posts = $this->posts();

		$posts->take( 10 );

		return $posts;
	}
}

class Testable_Term extends Term {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'test_taxonomy';

	/**
	 * @return Has_Many<static, Testable_Post>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Tag_Relationships extends Term {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'post_tag';

	/**
	 * @return Has_Many<static, Testable_Post>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Post_Using_Term extends Post {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'post';

	/**
	 * @return Belongs_To<static, Testable_Sponsor_Using_Term>
	 */
	public function sponsor(): Belongs_To {
		return $this->belongs_to( Testable_Sponsor_Using_Term::class )->uses_terms();
	}
}

class Testable_Sponsor_Using_Term extends Post {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'sponsor';

	/**
	 * @return Has_One<static, Testable_Post_Using_Term>
	 */
	public function post(): Has_One {
		return $this->has_one( Testable_Post_Using_Term::class )->uses_terms();
	}

	/**
	 * @return Has_Many<static, Testable_Post_Using_Term>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post_Using_Term::class )->uses_terms();
	}

	/**
	 * @return Has_Many<static, Testable_Post_Using_Term>
	 */
	public function posts_query_modifier(): Has_Many {
		$posts = $this->posts();

		$posts->take( 10 );

		return $posts;
	}
}
