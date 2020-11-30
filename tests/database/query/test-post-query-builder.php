<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Database\Query\Post_Query_Builder as Builder;
use Mantle\Framework\Database\Query\Post_Query_Builder;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Framework_Test_Case;


class Test_Post_Query_Builder extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();
		register_post_type( Another_Testable_Post::get_object_name() );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( Another_Testable_Post::get_object_name() );
	}

	public function test_post_by_name() {
		$post = static::factory()->post->create( [ 'post_name' => 'post-to-find' ] );

		$first = Builder::create( Testable_Post::class )
			->whereSlug( 'post-to-find' )
			->first();

		$this->assertInstanceOf( Testable_Post::class, $first );
		$this->assertEquals( 'post-to-find', $first->slug() );
		$this->assertEquals( $post, $first->id() );
	}

	public function test_post_by_name_not_found() {
		static::factory()->post->create( [ 'post_name' => 'post-to-not-find' ] );

		$first = Builder::create( Testable_Post::class )
			->whereSlug( 'post-name-we-are-looking-for' )
			->first();

		$this->assertNull( $first );
	}

	public function test_post_by_name_override() {
		static::factory()->post->create( [ 'post_name' => 'post-to-find' ] );

		$first = Builder::create( Testable_Post::class )
			->whereSlug( 'post-name-we-are-looking-for' )
			->whereSlug( 'post-to-find' )
			->first();

		$this->assertEquals( 'post-to-find', $first->slug() );
	}

	public function test_post__in() {
		$post_ids = static::factory()->post->create_many( 10 );

		// Shuffle to get a random order
		shuffle( $post_ids );

		$results = Builder::create( Testable_Post::class )
			->whereIn( 'id', $post_ids )
			->orderByPostIn( 'asc' )
			->get();

		$this->assertNotEmpty( $results );

		// Ensure the order matches.
		foreach ( $post_ids as $i => $post_id ) {
			$this->assertEquals( $post_id, $results[ $i ]->id() );
		}
	}

	public function test_where_array() {
		$post = static::factory()->post->create_and_get( [ 'post_name' => 'another-post-to-find' ] );
		$result = Builder::create( Testable_Post::class )
			->where(
				[
					'name' => $post->name,
				]
			)
			->first();

		$this->assertEquals( $post->ID, $result->id() );
	}

	public function test_post_meta() {
		$post_id = $this->get_random_post_id();
		update_post_meta( $post_id, 'meta-key', 'the-meta-value' );

		$first = Builder::create( Testable_Post::class )
			->whereMeta( 'meta-key', 'the-meta-value' )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_post_meta_or() {
		$post_id = $this->get_random_post_id();
		update_post_meta( $post_id, 'meta-key', 'the-meta-value' );

		$first = Builder::create( Testable_Post::class )
			->whereMeta( 'something', 'different' )
			->orWhereMeta( 'meta-key', 'the-meta-value' )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_post_meta_and() {
		$post_id = $this->get_random_post_id();
		update_post_meta( $post_id, 'meta-key', 'the-meta-value' );
		update_post_meta( $post_id, 'another-meta-key', 'another-meta-value' );

		$first = Builder::create( Testable_Post::class )
			->whereMeta( 'meta-key', 'the-meta-value' )
			->andWhereMeta( 'another-meta-key', 'another-meta-value' )
			->first();

		$this->assertEquals( $post_id, $first->id() );

		// Test against a post with only one meta value.
		$post_id_2 = $this->get_random_post_id();
		update_post_meta( $post_id_2, 'meta-key', 'the-meta-value' );

		$first = Builder::create( Testable_Post::class )
			->whereMeta( 'meta-key', 'the-meta-value' )
			->andWhereMeta( 'another-meta-key', 'another-meta-value' )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_term_query() {
		$post_id = $this->get_random_post_id();
		$term = static::factory()->term->create_and_get();
		wp_set_object_terms( $post_id, $term->term_id, $term->taxonomy, true );

		$first = Builder::create( Testable_Post::class )
			->whereTerm( $term )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_term_or_query() {
		$post_id = $this->get_random_post_id();
		$term_other = static::factory()->term->create();
		$term = static::factory()->term->create_and_get();
		wp_set_object_terms( $post_id, $term->term_id, $term->taxonomy, true );

		$first = Builder::create( Testable_Post::class )
			->whereTerm( $term_other )
			->orWhereTerm( $term )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_term_and_query() {
		$post_id = $this->get_random_post_id();
		$term_other = static::factory()->term->create();
		$term = static::factory()->term->create();
		wp_set_object_terms( $post_id, [ $term_other, $term ], 'post_tag', true );

		$first = Builder::create( Testable_Post::class )
			->whereTerm( $term_other )
			->andWhereTerm( $term )
			->first();

		$this->assertEquals( $post_id, $first->id() );

		// Test against a post with only one term.
		$post_id_2 = $this->get_random_post_id();
		wp_set_object_terms( $post_id_2, [ $term_other ], 'post_tag', true );

		$first = Builder::create( Testable_Post::class )
			->whereTerm( $term_other )
			->andWhereTerm( $term )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_term_query_from_term_model() {
		$post_id = $this->get_random_post_id();
		$term_other = static::factory()->term->create();
		$term = Testable_Tag::find( static::factory()->term->create() );

		wp_set_object_terms( $post_id, $term->id(), $term->taxonomy(), true );

		$first = Builder::create( Testable_Post::class )
			->whereTerm( $term_other )
			->orWhereTerm( $term )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_query_by_author() {
		$user_id = static::factory()->user->create();
		$post_id = $this->get_random_post_id();
		wp_update_post(
			[
				'ID'          => $post_id,
				'post_author' => $user_id,
			]
		);

		$first = Builder::create( Testable_Post::class )
			->whereAuthor( $user_id )
			->first();

		$this->assertEquals( $post_id, $first->id() );

		$first = Builder::create( Testable_Post::class )
			->whereIn( 'author', [ $user_id ] )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	public function test_query_by_author_name() {
		$user = static::factory()->user->create_and_get();
		$post_id = $this->get_random_post_id();
		wp_update_post(
			[
				'ID'          => $post_id,
				'post_author' => $user->ID,
			]
		);

		$first = Builder::create( Testable_Post::class )
			->whereAuthorName( $user->user_nicename )
			->first();

		$this->assertEquals( $post_id, $first->id() );
	}

	// public function test_query_with_multiple() {
	// 	$post_a = $this->get_random_post_id();
	// 	$post_b = $this->get_random_post_id( [ 'post_type' => Another_Testable_Post::get_object_name() ] );

	// 	update_post_meta( $post_a, 'shared-meta', 'meta-value' );
	// 	update_post_meta( $post_b, 'shared-meta', 'meta-value' );

	// 	update_post_meta( $post_a, 'meta-a', 'meta-value-a' );
	// 	update_post_meta( $post_b, 'meta-b', 'meta-value-b' );

	// 	$get = Post_Query_Builder::create( [ Testable_Post::class, Another_Testable_Post::class ] )
	// 		->whereMeta( 'shared-meta', 'meta-value' )
	// 		->get();

	// 	$this->assertEquals( 2, count( $get ) );
	// 	$this->assertEquals( $post_a, $get[0]->id() );
	// 	$this->assertEquals( $post_b, $get[1]->id() );

	// 	// Check querying one.
	// 	$get = Post_Query_Builder::create( [ Testable_Post::class, Another_Testable_Post::class ] )
	// 		->whereMeta( 'meta-b', 'meta-value-b' )
	// 		->get();

	// 	$this->assertEquals( 1, count( $get ) );
	// 	$this->assertEquals( $post_b, $get[0]->id() );
	// }

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

class Another_Testable_Post extends Post {
	public static $object_name = 'example-post-type';
}

class Testable_Tag extends Term {
	public static $object_name = 'post_tag';
}
