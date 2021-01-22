<?php
namespace Mantle\Tests\Database\Model\Relations\Has_One;

use Carbon\Carbon;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Framework\Database\Model\Relations\Relation;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Framework_Test_Case;
use Mantle\Framework\Testing\Utils;

class Test_Has_One_Or_Many extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		Utils::delete_all_posts();
		parent::setUp();
		register_post_type( 'sponsor' );
		register_taxonomy( 'test_taxonomy', 'post' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( 'sponsor' );
		unregister_taxonomy( 'test_taxonomy' );
	}

	public function test_has_one_post_to_post() {
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

		$this->assertEquals( $page->id, $post->page->id );
		$this->assertEquals( $page->id, $post->page()->first()->id );

		// Check the inverse of the relationship (belongs to).
		$this->assertEquals( $post->id, $page->post->id );
	}

	public function test_has_many_post_to_post() {
		Utils::delete_all_data();

		$date = Carbon::now()->subWeek();

		$post  = Testable_Post::create( [ 'title' => 'Test Post Many', 'date' => $date->toDateTimeString(), 'status' => 'publish' ] );
		$pages = [];

		for ( $i = 0; $i < 5; $i++ ) {
			$pages[] = $post->pages()->save(
				new Testable_Page( [
					'date'   => $date->subMinute()->toDateTimeString(),
					'status' => 'publish',
					'title'  => "Page {$i}",
				] )
			);
		}

		static::factory()->page->create_many( 10 );

		$this->assertCount( count( $pages ), $post->pages );

		// Assert that all pages exist and in the expected order.
		foreach ( $pages as $i => $page ) {
			$this->assertEquals( $page->id, $post->pages[ $i ]->id );

			// Check the inverse of the relationship (belongs to).
			$this->assertEquals( $post->id, $page->post->id );
		}
	}

	public function has_many_post_to_post_saving() {
		$post    = Testable_Post::find( $this->get_random_post_id() );
		$sponsor = Testable_Sponsor::find( $this->get_random_post_id( [ 'post_type' => 'sponsor' ] ) );

		// Save the post to the sponsor.
		$sponsor->posts()->save( $post );

		// Query the sponsor's post.
		$this->assertEquals( $post->id(), $sponsor->posts()->first()->id() );

		// Remove the sponsor from the post.
		$sponsor->posts()->remove( $post );

		// Query the relationship.
		$this->assertNull( $sponsor->posts()->first() );
	}

	public function test_has_one_post_to_term() {
		$post = Testable_Post::find( $this->get_random_post_id() );

		$tag = Testable_Tag::create(
			[
				'name' => 'Test Term Belongs To',
			]
		);

		$tag->posts()->save( $post );

		static::factory()->post->create_many( 10 );
		static::factory()->tag->create_many( 10 );

		$terms = get_the_tags( $post->id() );

		$this->assertNotEmpty( $terms );
		$this->assertEquals( $tag->id(), array_shift( $terms )->term_id );

		// Check the relationship.
		$this->assertEquals( $tag->id, $post->tag->id );

		$tag->posts()->remove( $post );
		$this->assertEmpty( get_the_terms( $post->id(), 'test_taxonomy' ) );
	}

	public function test_has_many_post_to_term() {
		$post = Testable_Post::find( $this->get_random_post_id() );
		$tags = [];

		for ( $i = 0; $i < 5; $i++ ) {
			$tags[] = $post->tags()->save( new Testable_Tag( [
				'name' => "Tag {$i}",
			] ) );
		}

		static::factory()->tag->create_many( 10 );

		$this->assertEquals( count( $tags ), count( get_the_tags( $post->id ) ) );
		$this->assertEquals( count( $tags ), count( $post->tags ) );

		// Check they match the expected tags.
		foreach ( $tags as $i => $tag ) {
			$this->assertEquals( $tag->id, $post->tags[ $i ]->id );
		}
	}

	public function test_has_one_term_to_post() {
		$tag = Testable_Tag::create( [ 'name' => 'Has One Term to Post' ] );

		$post = $tag->post()->save( new Testable_Post( [
			'date'   => Carbon::now()->subWeek()->toDateTimeString(),
			'name'   => 'Testable Post',
			'status' => 'publish',
		] ) );

		$this->assertEquals( $post->id, $tag->post->id );
		$this->assertEquals( $tag->id, get_the_tags( $post->id )[0]->term_id );

		$tag->post()->remove( $post );

		$this->assertEmpty( get_the_tags( $post->id ) );
	}

	public function test_has_many_term_to_post() {
		$tag = Testable_Tag::create( [ 'name' => 'Has Many Term to Post' ] );

		$posts = [];

		for ( $i = 0; $i < 5; $i++ ) {
			$posts[] = $tag->posts()->save( new Testable_Post( [
				'date'   => Carbon::now()->subWeek()->toDateTimeString(),
				'name'   => "Testable Post {$i}",
				'status' => 'publish',
			] ) );
		}

		$this->assertEquals( count( $posts ), count( $tag->posts ) );

		foreach ( $posts as $i => $post ) {
			$this->assertTrue( has_tag( $tag->id, $post->id ) );
			$this->assertEquals( $post->id, $tag->posts[ $i ]->id );
		}
	}

	public function test_has_many_post_to_post_with_terms() {
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
		$this->assertEmpty( $post->meta->testable_sponsor_using_term_id );
		$this->assertNotEmpty( get_the_terms( $post->id, Relation::RELATION_TAXONOMY ) );

		$sponsors_post = $sponsor->post()->first();

		$this->assertInstanceOf( Testable_Post_Using_Term::class, $sponsors_post );
		$this->assertEquals( $post->id, $sponsors_post->id );

		// Delete the relationship and ensure the internal term is removed.
		$sponsor->post()->remove( $post );
		$this->assertEmpty( get_the_terms( $post->id, Relation::RELATION_TAXONOMY ) );
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @return int
	 */
	protected function get_random_post_id( $args = [] ): int {
		$post_ids = static::factory()->post->create_many( 11, $args );
		array_pop( $post_ids );
		return $post_ids[ array_rand( $post_ids ) ];
	}
}

class Testable_Post extends Post {
	use Relationships;

	public static $object_name = 'post';

	public function page() {
		return $this->has_one( Testable_Page::class );
	}

	public function pages() {
		return $this->has_many( Testable_Page::class );
	}

	public function tag() {
		return $this->has_one( Testable_Tag::class );
	}

	public function tags() {
		return $this->has_many( Testable_Tag::class );
	}

	public function sponsor() {
		return $this->belongs_to( Testable_Sponsor::class );
	}

	public function terms() {
		return $this->has_many( Testable_Term::class );
	}
}

class Testable_Page extends Post {
	use Relationships;

	public static $object_name = 'page';

	public function post() {
		return $this->belongs_to( Testable_Post::class );
	}
}

class Testable_Tag extends Term {
	use Relationships;

	public static $object_name = 'post_tag';

	public function post() {
		return $this->has_one( Testable_Post::class );
	}

	public function posts() {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Sponsor extends Post {
	use Relationships;

	public static $object_name = 'sponsor';

	public function post() {
		return $this->has_one( Testable_Post::class );
	}

	public function posts() {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Term extends Term {
	use Relationships;

	public static $object_name = 'test_taxonomy';

	public function posts() {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Tag_Relationships extends Term {
	use Relationships;

	public static $object_name = 'post_tag';

	public function posts() {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Post_Using_Term extends Post {
	use Relationships;

	public static $object_name = 'post';

	public function sponsor() {
		return $this->belongs_to( Testable_Sponsor_Using_Term::class )->uses_terms();
	}
}

class Testable_Sponsor_Using_Term extends Post {
	use Relationships;

	public static $object_name = 'sponsor';

	public function post() {
		return $this->has_one( Testable_Post_Using_Term::class )->uses_terms();
	}
}
