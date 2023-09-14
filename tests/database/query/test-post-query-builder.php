<?php
namespace Mantle\Tests\Database\Builder;

use Carbon\Carbon;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Database\Query\Post_Query_Builder as Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Support\Collection;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Utils;

use function Mantle\Support\Helpers\collect;

class Test_Post_Query_Builder extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		Utils::delete_all_data();

		register_post_type( Another_Testable_Post::get_object_name() );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( Another_Testable_Post::get_object_name() );
	}

	public function test_post_by_name() {
		$post = static::factory()->post->create( [ 'post_name' => 'post-to-find' ] );

		$first = Testable_Post::whereSlug( 'post-to-find' )->first();

		$this->assertInstanceOf( Testable_Post::class, $first );
		$this->assertEquals( 'post-to-find', $first->slug() );
		$this->assertEquals( $post, $first->id() );
	}

	public function test_post_by_name_not_found() {
		static::factory()->post->create( [ 'post_name' => 'post-to-not-find' ] );

		$first = Testable_Post::whereSlug( 'post-name-we-are-looking-for' )->first();

		$this->assertNull( $first );
	}

	public function test_post_by_name_override() {
		static::factory()->post->create( [ 'post_name' => 'post-to-find' ] );

		$b = Testable_Post::whereSlug( 'post-name-we-are-looking-for' )
			->whereSlug( 'post-to-find' );

		$first = Testable_Post::whereSlug( 'post-name-we-are-looking-for' )
			->whereSlug( 'post-to-find' )
			->first();

		$this->assertEquals( 'post-to-find', $first->slug() );
	}

	public function test_post__in() {
		$post_ids = static::factory()->post->create_ordered_set( 10 );

		// Shuffle to get a random order
		$post_ids = collect( $post_ids )->shuffle()->values()->all();

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

	public function test_query_with_multiple() {
		Utils::delete_all_data();

		$post_a = static::factory()->post->create( [ 'post_date' => Carbon::now()->subWeek()->subHour()->toDateTimeString() ] );
		$post_b = static::factory()->post->create( [
			'post_date' => Carbon::now()->subWeek()->toDateTimeString(),
			'post_type' => Another_Testable_Post::get_object_name(),
		] );

		// Create some posts after for some randomness.
		static::factory()->post->create_many( 10 );
		static::factory()->post->create_many( 10, [ 'post_type' => Another_Testable_Post::get_object_name() ] );

		update_post_meta( $post_a, 'shared-meta', 'meta-value' );
		update_post_meta( $post_b, 'shared-meta', 'meta-value' );

		update_post_meta( $post_a, 'meta-a', 'meta-value-a' );
		update_post_meta( $post_b, 'meta-b', 'meta-value-b' );

		$get = Post_Query_Builder::create( [ Testable_Post::class, Another_Testable_Post::class ] )
			->whereMeta( 'shared-meta', 'meta-value' )
			->orderBy( 'post_date', 'ASC' )
			->get();

		$this->assertEquals( 2, count( $get ) );
		$this->assertEquals( $post_a, $get[0]->id() );
		$this->assertEquals( $post_b, $get[1]->id() );

		// Check querying one.
		$get = Post_Query_Builder::create( [ Testable_Post::class, Another_Testable_Post::class ] )
			->whereMeta( 'meta-b', 'meta-value-b' )
			->get();

		$this->assertEquals( 1, count( $get ) );
		$this->assertEquals( $post_b, $get[0]->id() );
	}

	public function test_delete_posts() {
		Utils::delete_all_data();

		static::factory()->post->create_many( 10 );

		$post_ids = static::factory()->post->create_many(
			10,
			[
				'meta' => [
					'meta-key' => 'meta-value',
				]
			]
		);

		static::factory()->post->create_many( 10 );

		// Attempt to delete the posts.
		Testable_Post::whereMeta( 'meta-key', 'meta-value' )->delete( true );

		// Ensure the posts are deleted.
		foreach ( $post_ids as $post_id ) {
			$this->assertEmpty( get_post( $post_id ) );
		}
	}

	public function test_query_clauses() {
		$applied_count = 0;
		$post_id       = $this->get_random_post_id();

		$first = Testable_Post::query()
			->add_clause(
				function ( array $clauses ) use ( &$applied_count, $post_id ) {
					$applied_count++;

					$clauses['where'] .= ' AND ID = ' . $post_id;

					return $clauses;
				}
			)
			->first();

		$this->assertEquals( $post_id, $first->id() );

		$next = Testable_Post::first();

		$this->assertNotEquals( $post_id, $next->id() );
		$this->assertEquals( 1, $applied_count ); // The clauses should only be applied once.
	}

	public function test_chunk() {
		static::factory()->post->create_many( 101 );

		$last_page = null;
		$count     = 0;
		$ids       = new Collection();

		$result = Testable_Post::chunk( 10, function ( Collection $results, int $page ) use ( &$count, &$last_page, &$ids ) {
			if ( ! isset( $last_page ) ) {
				$last_page = $page;
			} else {
				$this->assertGreaterThan( $last_page, $page );
			}

			$count += count( $results );

			$this->assertInstanceof( Collection::class, $results );

			if ( $page <= 10 ) {
				$this->assertEquals( 10, $results->count() );
			} else {
				$this->assertEquals( 1, $results->count() );
			}

			// Ensure that all the posts are unique from the previous ones.
			$new_ids = $results->pluck( 'id' );

			$this->assertEmpty(
				$new_ids->intersect( $ids )
			);

			$ids = $ids->merge( $new_ids );
		} );

		$this->assertTrue( $result );
		$this->assertEquals( 101, $count );
	}

	public function test_chunk_short_circuit() {
		static::factory()->post->create_many( 101 );

		$count = 0;

		$result = Testable_Post::chunk( 10, function ( Collection $results, int $page ) use ( &$count ) {
			$count += count( $results );

			return false;
		} );

		$this->assertFalse( $result );
		$this->assertEquals( 10, $count );
	}

	public function test_chunk_by_id() {
		$post_ids = static::factory()->post->create_many( 105 );

		$last_page = null;
		$count     = 0;
		$ids       = new Collection();

		$result = Testable_Post::chunk_by_id( 10, function ( Collection $results, int $page ) use ( &$count, &$last_page, &$ids ) {
			if ( isset( $last_page ) ) {
				$this->assertGreaterThan( $last_page, $page );
			}

			$count += $results->count();

			if ( $page <= 10 ) {
				$this->assertEquals( 10, $results->count() );
			} else {
				$this->assertEquals( 5, $results->count() );
			}

			// Ensure that all the posts are unique from the previous ones.
			$new_ids = $results->pluck( 'id' );

			$this->assertEmpty( $new_ids->intersect( $ids ) );

			$ids = $ids->merge( $new_ids );

			// Delete all the posts that were returned for true chunk by ID.
			$results->each->delete( true );

			$last_page = $page;
		} );

		$this->assertTrue( $result );
		$this->assertEquals( 105, $count );

		$this->assertNull( get_post( collect( $post_ids )->last() ) );
	}

	public function test_each() {
		$post_ids = static::factory()->post->create_many( 100 );

		$ids = new Collection();

		Testable_Post::each( function ( Testable_Post $post ) use ( &$ids ) {
			$ids->push( $post->id() );
		} );

		$this->assertEquals( collect( $post_ids )->sort()->values(), $ids->sort()->values() );
	}

	public function test_each_by_id() {
		$post_ids = static::factory()->post->create_many( 100 );

		$ids = new Collection();

		Testable_Post::each_by_id( function ( Testable_Post $post ) use ( &$ids ) {
			$ids->push( $post->id() );

			$post->delete( true );
		} );

		$this->assertEquals( collect( $post_ids )->sort()->values(), $ids->sort()->values() );

		$this->assertNull( get_post( collect( $post_ids )->last() ) );
	}

	public function test_where_raw() {
		$post_id = static::get_random_post_id();

		$result = Testable_Post::query()
			->where_raw( 'ID', $post_id )
			->first();

		$this->assertEquals( $result->id(), $post_id );
	}

	public function test_where_raw_like() {
		$post_id = static::get_random_post_id();

		$result = Testable_Post::query()
			->where_raw( 'post_title', 'LIKE', get_the_title( $post_id ) )
			->first();

		$this->assertEquals( $result->id(), $post_id );

		$post_id = Testable_Post::factory()->create( [
			'post_title' => 'This is a post title',
		] );

		$result = Testable_Post::query()
			->order_by( 'id', 'asc' )
			->where_raw( 'post_title', 'LIKE', 'This is a%' )
			->first();

		$this->assertEquals( $result->id(), $post_id );
	}

	public function test_where_in_raw() {
		$post_ids = static::factory()->post->create_many( 10 );

		// Shuffle to get a random order
		$post_ids = collect( $post_ids )->shuffle()->values();

		$expected = $post_ids->random( 3 )->values();

		$results = Builder::create( Testable_Post::class )
			->whereRaw( 'ID', 'IN', $expected->all() )
			->orderBy( 'id', 'asc' )
			->get();

		$this->assertNotEmpty( $results );

		$this->assertCount( 3, $results );
		$this->assertEquals(
			$expected->sort()->values()->all(),
			$results->pluck( 'id' )->all()
		);
	}

	public function test_where_raw_or() {
		$post_id = static::get_random_post_id();

		$result = Testable_Post::whereRaw( 'ID', 'unknown' )
			->orWhereRaw( 'ID', $post_id )
			->first();

		$this->assertEquals( $result->id(), $post_id );
	}

	public function test_count() {
		static::factory()->post->create_many( 14, [ 'post_status' => 'draft' ] );
		static::factory()->post->create_many( 17, [ 'post_status' => 'publish' ] );

		$this->assertEquals( 14, Testable_Post::whereStatus( 'draft' )->count() );
		$this->assertEquals( 17, Testable_Post::whereStatus( 'publish' )->count() );
		$this->assertEquals( 31, Testable_Post::anyStatus()->count() );

		$post_id = static::get_random_post_id();

		$this->assertEquals( 1, Testable_Post::whereIn( 'id', [ $post_id ] )->count() );
	}

	public function test_post_by_date() {
		$old_date = Carbon::now( wp_timezone() )->subMonth();
		$now      = Carbon::now( wp_timezone() );

		$old_post_id = Testable_Post::factory()->create( [
			'post_date' => $old_date->toDateTimeString(),
		] );

		$now_post_id = Testable_Post::factory()->create( [
			'post_date' => $now->toDateTimeString(),
		] );

		$this->assertEquals(
			$old_post_id,
			Testable_Post::query()->whereDate( $old_date )->first()?->id,
		);

		$this->assertEquals(
			$now_post_id,
			Testable_Post::query()->whereDate( $now )->first()?->id,
		);

		$this->assertEquals(
			$old_post_id,
			Testable_Post::query()->where( 'date', $old_date )->first()?->id,
		);

		$this->assertEquals(
			$now_post_id,
			Testable_Post::query()->whereDate( $old_date, '!=' )->first()?->id,
		);

		$this->assertEquals(
			$old_post_id,
			Testable_Post::query()->whereDate( $now, '!=' )->first()?->id,
		);
	}

	public function test_post_by_modified_date() {
		$old_date = Carbon::now( wp_timezone() )->subMonth();
		$now      = Carbon::now( wp_timezone() );

		$old_post_id = Testable_Post::factory()->create();
		$now_post_id = Testable_Post::factory()->create();

		$this->update_post_modified( $old_post_id, $old_date );
		$this->update_post_modified( $now_post_id, $now );

		$this->assertEquals(
			$old_post_id,
			Testable_Post::query()->whereModifiedDate( $old_date )->first()?->id,
		);

		$this->assertEquals(
			$now_post_id,
			Testable_Post::query()->whereModifiedDate( $now )->first()?->id,
		);

		$this->assertEquals(
			$now_post_id,
			Testable_Post::query()->whereModifiedDate( $old_date, '!=' )->first()?->id,
		);

		$this->assertEquals(
			$old_post_id,
			Testable_Post::query()->whereModifiedDate( $now, '!=' )->first()?->id,
		);
	}

	/**
	 * @dataProvider date_comparison_provider
	 */
	public function test_date_comparisons( int $expected, string $method, array $args ) {
		$start = Carbon::now( wp_timezone() )->subMonth()->startOfDay();

		static::factory()->post->create_ordered_set( 20, [], $start->clone() );

		$this->assertEquals(
			$expected,
			Testable_Post::query()->{$method}( ...$args )->count(),
		);
	}

	public static function date_comparison_provider(): array {
		$start = Carbon::now( wp_timezone() )->subMonth()->startOfDay();

		return [
			// Older than now should return all posts.
			'older_than_now' => [ 20, 'olderThan', [ Carbon::now( wp_timezone() ) ] ],
			// Older than the start date should return no posts.
			'older_than_start' => [ 0, 'olderThan', [ $start ] ],
			// Older than or equal to the start date should return the first post.
			'older_than_or_equal_to_start' => [ 1, 'olderThanOrEqualTo', [ $start ] ],
			// Older than 5 hrs from start should return 5 posts.
			'older_than_5_hrs' => [ 5, 'olderThan', [ $start->clone()->addHours( 5 ) ] ],
			// Newer than 5 hrs from start should return 14 posts.
			'newer_than_5_hrs' => [ 14, 'newerThan', [ $start->clone()->addHours( 5 ) ] ],
			// Newer than or equal to 5 hrs from start should return 15 posts.
			'newer_than_or_equal_to_5_hrs' => [ 15, 'newerThanOrEqualTo', [ $start->clone()->addHours( 5 ) ] ],
			// Older than the middle post should return 10 posts.
			'older_than_middle' => [ 10, 'olderThan', [ $start->clone()->addHours( 10 ) ] ],
			// Newer than the middle post should return 10 posts.
			'newer_than_middle' => [ 10, 'newerThanOrEqualTo', [ $start->clone()->addHours( 10 ) ] ],
		];
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @return integer
	 */
	protected static function get_random_post_id( $args = [] ): int {
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
