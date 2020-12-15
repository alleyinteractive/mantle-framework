<?php
namespace Mantle\Tests\Database\Builder\Post_Query_Relationships;

use Carbon\Carbon;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Framework\Database\Model\Relations\Relation;
use Mantle\Framework\Testing\Framework_Test_Case;


class Test_Post_Query_Relationships extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();
		register_post_type( Testable_Sponsor::get_object_name() );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( Testable_Sponsor::get_object_name() );
	}

	public function test_relationship_has() {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$first = Testable_Post::has( 'sponsor' )->first();
		$this->assertEquals( $post_a, $first->id() );
	}

	public function test_relationship_has_compare() {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$missing = Testable_Post::has( 'sponsor', 'non-exist' )->first();
		$this->assertEmpty( $missing );

		$expected = Testable_Post::has( 'sponsor', $sponsor_id )->first();
		$this->assertEquals( $post_a, $expected->id() );
	}

	public function test_relationship_doesnt_have() {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$first = Testable_Post::doesnt_have( 'sponsor' )->first();
		$this->assertNotEquals( $post_a, $first->id() );
	}

	public function test_has_many_post_to_post_with_terms() {
		$sponsor = Testable_Sponsor_Using_Term::create( [ 'title' => 'Sponsor Test', 'status' => 'publish' ] );
		$post    = $sponsor->post()->save(
			new Testable_Post_Using_Term(
				[
					'title' => 'Post with Sponsor',
					'status' => 'publish',
					'post_date' => Carbon::now()->subDays( 7 )->toDateTimeString(),
				]
			)
		);

		// Create some posts after the current.
		static::factory()->post->create_many( 10 );

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

	public function test_belongs_to_post_to_post_with_terms() {
		$post = Testable_Post_Using_Term::create(
			[
				'title' => 'Post with Sponsor Belongs To',
				'status' => 'publish',
				'post_date' => Carbon::now()->subDays( 5 )->toDateTimeString(),
			]
		);

		$sponsor = $post->sponsor()->associate(
			new Testable_Sponsor_Using_Term(
				[
					'title' => 'Sponsor Test Belongs To',
					'status' => 'publish',
					'post_date' => Carbon::now()->subDays( 5 )->toDateTimeString(),
				]
			)
		);

		// Create some posts after the current.
		static::factory()->post->create_many( 10 );
		static::factory()->post->create_many( 10, [ 'post_type' => 'sponsor' ] );

		// Ensure it wasn't set using the post meta.
		$this->assertEmpty( $post->meta->testable_sponsor_using_term_id );
		$this->assertNotEmpty( get_the_terms( $post->id, Relation::RELATION_TAXONOMY ) );

		// Retrieve the relationship and compare.
		$post_sponsor = $post->sponsor()->first();

		$this->assertInstanceOf( Testable_Sponsor_Using_Term::class, $post_sponsor );
		$this->assertEquals( $sponsor->id(), $post_sponsor->id() );

		// Delete the relationship and ensure the internal term is removed.
		$post->sponsor()->dissociate();
		$this->assertEmpty( get_the_terms( $post->id, Relation::RELATION_TAXONOMY ) );
	}

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
	use Relationships;
	public static $object_name = 'post';

	public function sponsor() {
		return $this->belongs_to( Testable_Sponsor::class );
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
