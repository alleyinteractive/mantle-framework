<?php
namespace Mantle\Tests\Database\Builder\Post_Query_Relationships;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Database\Model\Term;
use Mantle\Database\Model_Service_Provider;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Utils;

class Test_Post_Query_Relationships extends Framework_Test_Case {
	protected function setUp(): void {
		Utils::delete_all_posts();
		parent::setUp();
		register_post_type( Testable_Sponsor::get_object_name() );

		if ( ! taxonomy_exists( Relation::RELATION_TAXONOMY ) ) {
			Model_Service_Provider::register_internal_taxonomy();
		}
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

	public function tags() {
		return $this->has_many( Testable_Tag_Relationships::class );
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

class Testable_Tag_Relationships extends Term {
	use Relationships;

	public static $object_name = 'post_tag';

	public function posts() {
		return $this->has_many( Testable_Post::class );
	}
}
