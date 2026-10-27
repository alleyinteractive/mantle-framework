<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Database\Model\Term;
use Mantle\Database\Query\Term_Query_Builder as Builder;
use Mantle\Testing\FrameworkTestCase;

use function Mantle\Support\Helpers\collect;

class TermQueryBuilderTest extends FrameworkTestCase {
	public function test_term_by_name(): void {
		$tag_id = $this->get_random_term_id();
		$tag    = \get_term( $tag_id );

		$this->assertInstanceOf( \WP_Term::class, $tag );

		$first = Builder::create( Testable_Tag_Term::class )
			->whereName( $tag->name )
			->first();

		$this->assertInstanceOf( Testable_Tag_Term::class, $first );

		$this->assertSame( $tag_id, $first->id() );
	}

	public function test_term_by_slug(): void {
		$tag_id = $this->get_random_term_id();
		$tag    = \get_term( $tag_id );

		$this->assertInstanceOf( \WP_Term::class, $tag );

		$first = Testable_Tag_Term::query()
			->whereSlug( $tag->slug )
			->first();

		$this->assertInstanceOf( Testable_Tag_Term::class, $first );

		$this->assertSame( $tag_id, $first->id() );
	}

	public function test_term_by_id(): void {
		$tag_id = $this->get_random_term_id();
		$tag    = \get_term( $tag_id );

		$this->assertInstanceOf( \WP_Term::class, $tag );

		$first = Builder::create( Testable_Tag_Term::class )
			->whereId( $tag->term_id )
			->first();

		$this->assertInstanceOf( Testable_Tag_Term::class, $first );

		$this->assertSame( $tag_id, $first->id() );
	}

	public function test_term_by_meta(): void {
		$tag_id = $this->get_random_term_id();

		update_term_meta( $tag_id, 'term-key', 'term-value' );

		$first = Builder::create( Testable_Tag_Term::class )
			->whereMeta( 'term-key', 'term-value' )
			->first();

		$this->assertInstanceOf( Testable_Tag_Term::class, $first );

		$this->assertSame( $tag_id, $first->id() );
	}

	public function test_term_exclude(): void {
		$tag_id = $this->get_random_term_id();

		$get = Builder::create( Testable_Tag_Term::class )
			->whereNotIn( 'id', [ $tag_id ] )
			->get()
			->pluck( 'id' );

		$this->assertNotContains( $tag_id, $get->to_array() );
	}

	public function test_term_orderby_asc(): void {
		$term_ids = collect( static::factory()->term->create_many( 10 ) );

		$get = Testable_Tag_Term::query()
			->whereIn( 'id', $term_ids->shuffle()->values()->all() )
			->orderBy( 'id', 'asc' )
			->get()
			->pluck( 'id' );

		$this->assertSame( $term_ids->sort()->values()->to_array(), $get->to_array() );
	}

	public function test_term_orderby_desc(): void {
		$term_ids = collect( static::factory()->term->create_many( 10 ) );

		$get = Testable_Tag_Term::query()
			->whereIn( 'id', $term_ids->shuffle()->values()->all() )
			->orderBy( 'id', 'desc' )
			->get()
			->pluck( 'id' );

		$this->assertSame( $term_ids->sort_desc()->values()->to_array(), $get->to_array() );
	}

	public function test_term_orderby_include(): void {
		$term_ids = static::factory()->term->create_many( 10 );

		// Shuffle to get a random order
		shuffle( $term_ids );

		$get = Testable_Tag_Term::query()
			->whereIn( 'id', $term_ids )
			->orderByWhereIn( 'id' )
			->get()
			->pluck( 'id' );

		$this->assertSame( $term_ids, $get->to_array() );
	}

	public function test_query_clauses(): void {
		$applied_count = 0;
		$term_id       = $this->get_random_term_id();

		$first = Testable_Tag::query()
			->orderBy( 'id', 'asc' )
			->add_clause(
				static function ( array $clauses ) use ( &$applied_count, $term_id ) {
					global $wpdb;

					$applied_count++;

					$clauses['where'] .= $wpdb->prepare( ' AND t.term_id = %d', $term_id );

					return $clauses;
				}
			)
			->firstOrFail();

		$this->assertSame( $term_id, $first->id() );

		$next = Testable_Tag::query()
			->order_by( 'id', 'asc' )
			->first();

		$this->assertInstanceOf( Testable_Tag::class, $next );

		$this->assertNotSame( $term_id, $next->id() );
		$this->assertSame( 1, $applied_count ); // The clauses should only be applied once.
	}

	public function test_for_page(): void {
		static::factory()->tag->create_many( 5 );

		$page_one   = Testable_Tag::query()->for_page( 1, 2 )->get()->pluck( 'id' );
		$page_two   = Testable_Tag::query()->for_page( 2, 2 )->get()->pluck( 'id' );
		$page_three = Testable_Tag::query()->for_page( 3, 2 )->get()->pluck( 'id' );

		$this->assertCount( 2, $page_one );
		$this->assertCount( 2, $page_two );
		$this->assertCount( 1, $page_three );
		$this->assertEmpty( $page_one->intersect( $page_two )->all() );
		$this->assertEmpty( $page_two->intersect( $page_three )->all() );
	}

	public function test_chunk(): void {
		static::factory()->tag->create_many( 5 );

		$chunks = [];
		$ids    = [];

		Testable_Tag::query()->chunk(
			2,
			static function ( $terms ) use ( &$chunks, &$ids ): void {
				$chunks[] = $terms->count();
				$ids      = array_merge( $ids, $terms->pluck( 'id' )->all() );
			},
		);

		$this->assertEquals( [ 2, 2, 1 ], $chunks );
		$this->assertCount( 5, array_unique( $ids ) );
	}

	public function test_count(): void {
		static::factory()->tag->create_many( 14 );

		$this->assertSame( 14, Testable_Tag::count() );
		$this->assertSame( 14, Testable_Tag::query()->count() );

		$tag_id = $this->get_random_term_id();

		// Ensure that query clauses are applied.
		$this->assertSame(
			1,
			Testable_Tag::whereIn( 'id', [ $tag_id ] )->count(),
		);
	}

	/**
	 * Get a random term ID, ensures the term ID is not the last in the set.
	 *
	 * @param array<string, mixed> $args Arguments for the terms.
	 * @return integer
	 */
	protected function get_random_term_id( $args = [] ): int {
		$term_ids = static::factory()->term->create_many( 11, $args );

		array_pop( $term_ids );
		array_shift( $term_ids );

		return $term_ids[ array_rand( $term_ids ) ];
	}
}

/**
 * @method static int count()
 */
class Testable_Tag extends Term {
	public static $object_name = 'post_tag';
}

class Testable_Category extends Term {
	public static $object_name = 'category';
}

class Testable_Tag_Term extends Term {
	public static $object_name = 'post_tag';
}
