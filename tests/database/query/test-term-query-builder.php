<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Database\Model\Term;
use Mantle\Database\Query\Term_Query_Builder as Builder;
use Mantle\Testing\Framework_Test_Case;


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

		$first = Builder::create( Testable_Tag_Term::class )
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

	public function test_term_orderby_include() {
		$term_ids = static::factory()->term->create_many( 10 );

		// Shuffle to get a random order
		shuffle( $term_ids );

		$get = Builder::create( Testable_Tag_Term::class )
			->whereIn( 'id', $term_ids )
			->orderBy( 'id' )
			->get()
			->pluck( 'id' );

		$this->assertEquals( $term_ids, $get->to_array() );

	}

	/**
	 * Get a random term ID, ensures the term ID is not the last in the set.
	 *
	 * @return integer
	 */
	protected function get_random_term_id( $args = [] ): int {
		$term_ids = static::factory()->term->create_many( 11, $args );
		array_pop( $term_ids );
		return $term_ids[ array_rand( $term_ids ) ];
	}
}

class Testable_Category extends Term {
	public static $object_name = 'category';
}

class Testable_Tag_Term extends Term {
	public static $object_name = 'post_tag';
}
