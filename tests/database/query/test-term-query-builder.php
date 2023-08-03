<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Database\Model\Term;
use Mantle\Database\Query\Term_Query_Builder as Builder;
use Mantle\Testing\Framework_Test_Case;

use function Mantle\Support\Helpers\collect;

class Test_Term_Query_Builder extends Framework_Test_Case {
	public function test_term_by_name() {
		$tag_id = $this->get_random_term_id();
		$tag    = \get_term( $tag_id );

		$first = Builder::create( Testable_Tag_Term::class )
			->whereName( $tag->name )
			->first();

		$this->assertEquals( $tag_id, $first->id() );
	}

	public function test_term_by_slug() {
		$tag_id = $this->get_random_term_id();
		$tag    = \get_term( $tag_id );

		$first = Testable_Tag_Term::query()
			->whereSlug( $tag->slug )
			->first();

		$this->assertEquals( $tag_id, $first->id() );
	}

	public function test_term_by_id() {
		$tag_id = $this->get_random_term_id();
		$tag    = \get_term( $tag_id );

		$first = Builder::create( Testable_Tag_Term::class )
			->whereId( $tag->term_id )
			->first();

		$this->assertEquals( $tag_id, $first->id() );
	}

	public function test_term_by_meta() {
		$tag_id = $this->get_random_term_id();

		update_term_meta( $tag_id, 'term-key', 'term-value' );

		$first = Builder::create( Testable_Tag_Term::class )
			->whereMeta( 'term-key', 'term-value' )
			->first();

		$this->assertEquals( $tag_id, $first->id() );
	}

	public function test_term_exclude() {
		$tag_id = $this->get_random_term_id();

		$get = Builder::create( Testable_Tag_Term::class )
			->whereNotIn( 'id', [ $tag_id ] )
			->get()
			->pluck( 'id' );

		$this->assertFalse( in_array( $tag_id, $get->to_array(), true ) );
	}

	public function test_term_orderby_asc() {
		$term_ids = collect( static::factory()->term->create_many( 10 ) );

		$get = Testable_Tag_Term::query()
			->whereIn( 'id', $term_ids->shuffle()->values()->all() )
			->orderBy( 'id', 'asc' )
			->get()
			->pluck( 'id' );

		$this->assertEquals( $term_ids->sort()->values()->to_array(), $get->to_array() );
	}

	public function test_term_orderby_desc() {
		$term_ids = collect( static::factory()->term->create_many( 10 ) );

		$get = Testable_Tag_Term::query()
			->whereIn( 'id', $term_ids->shuffle()->values()->all() )
			->orderBy( 'id', 'desc' )
			->get()
			->pluck( 'id' );

		$this->assertEquals( $term_ids->sort_desc()->values()->to_array(), $get->to_array() );
	}

	public function test_term_orderby_include() {
		$term_ids = static::factory()->term->create_many( 10 );

		// Shuffle to get a random order
		shuffle( $term_ids );

		$get = Testable_Tag_Term::query()
			->whereIn( 'id', $term_ids )
			->orderByWhereIn( 'id' )
			->get()
			->pluck( 'id' );

		$this->assertEquals( $term_ids, $get->to_array() );
	}

	public function test_query_clauses() {
		$applied_count = 0;
		$term_id       = $this->get_random_term_id();

		$first = Testable_Tag::query()
			->add_clause(
				function ( array $clauses ) use ( &$applied_count, $term_id ) {
					global $wpdb;

					$applied_count++;

					$clauses['where'] .= $wpdb->prepare( ' AND t.term_id = %d', $term_id );

					return $clauses;
				}
			)
			->firstOrFail();

		$this->assertEquals( $term_id, $first->id() );

		$next = Testable_Tag::first();

		$this->assertNotEquals( $term_id, $next->id() );
		$this->assertEquals( 1, $applied_count ); // The clauses should only be applied once.
	}

	/**
	 * Get a random term ID, ensures the term ID is not the last in the set.
	 *
	 * @return integer
	 */
	protected function get_random_term_id( $args = [] ): int {
		$term_ids = static::factory()->term->create_many( 11, $args );

		array_pop( $term_ids );
		array_shift( $term_ids );

		return $term_ids[ array_rand( $term_ids ) ];
	}
}

class Testable_Category extends Term {
	public static $object_name = 'category';
}

class Testable_Tag_Term extends Term {
	public static $object_name = 'post_tag';
}
