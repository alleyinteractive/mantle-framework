<?php
namespace Mantle\Tests\Database\Model\Relations;

use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Relationships;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Testing\Framework_Test_Case;


class Test_Post_Object extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();
		register_post_type( 'sponsor' );
		register_taxonomy( 'test_taxonomy', 'post' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( 'sponsor' );
		unregister_taxonomy( 'test_taxonomy' );
	}

	public function test_get_belongs_to() {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Get the sponsor via a belongs_to relationship.
		$object = Testable_Post::find( $post_a );
		$object->set_meta( 'testable_sponsor_id', $sponsor_id );

		$first = $object->sponsor()->first();
		$this->assertEquals( $sponsor_id, $first->id() );
	}

	public function test_get_has_one() {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$object = Testable_Sponsor::find( $sponsor_id );
		$first = $object->post()->first();
		$this->assertEquals( $post_a, $first->id() );
	}

	public function test_get_has_many() {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$object = Testable_Sponsor::find( $sponsor_id );
		$first = $object->posts()->first();
		$this->assertEquals( $post_a, $first->id() );
	}

	public function test_saving_model_belongs_to() {
		$post    = Testable_Post::find( $this->get_random_post_id() );
		$sponsor = Testable_Sponsor::find( $this->get_random_post_id( [ 'post_type' => 'sponsor' ] ) );

		// Save the post's sponsor.
		$post->sponsor()->associate( $sponsor );

		// Check that the expected meta was set.
		$this->assertEquals( $sponsor->id(), $post->get_meta( 'testable_sponsor_id' ) );

		// Test querying against the relationship.
		$this->assertEquals( $sponsor->id(), $post->sponsor()->first()->id() );

		// Remove the relationship and expect the meta removed.
		$post->sponsor()->dissociate();

		// Query against the now-removed relationship.
		$this->assertNull( $post->sponsor()->first() );
	}

	public function test_saving_model_has_many() {
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

	public function test_has_one_term() {
		$post = Testable_Post::find( $this->get_random_post_id() );

		$term = Testable_Term::create(
			[
				'name' => 'Test Term Has One',
			]
		);

		$post->terms()->save( $term );

		$terms = get_the_terms( $post->id(), 'test_taxonomy' );

		$this->assertNotEmpty( $terms );
		$this->assertEquals( $term->id(), array_shift( $terms )->term_id );

		$post->terms()->remove( $term );
		$this->assertEmpty( get_the_terms( $post->id(), 'test_taxonomy' ) );
	}

	public function test_has_one_through_term() {
		$post = Testable_Post::find( $this->get_random_post_id() );

		$term = Testable_Term::create(
			[
				'name' => 'Test Term Belongs To',
			]
		);

		$term->posts()->save( $post );

		$terms = get_the_terms( $post->id(), 'test_taxonomy' );

		$this->assertNotEmpty( $terms );
		$this->assertEquals( $term->id(), array_shift( $terms )->term_id );

		$term->posts()->remove( $post );
		$this->assertEmpty( get_the_terms( $post->id(), 'test_taxonomy' ) );
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

	public function sponsor() {
		return $this->belongs_to( Testable_Sponsor::class );
	}

	public function terms() {
		return $this->has_many( Testable_Term::class );
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
