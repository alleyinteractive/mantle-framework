<?php
namespace Mantle\Tests\Database\Model\Relations;

use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
use Mantle\Framework\Database\Model\Relations\Has_One;
use Mantle\Framework\Database\Model\Relationships;
use Mantle\Framework\Database\Query\Post_Query_Builder;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Post_Object extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		register_post_type( 'test-child-post' );
	}

	public function tearDown() {
		parent::tearDown();
		unregister_post_type( 'test-child-post' );
	}

	public function test_get_has_one() {
		$post_a = $this->get_random_post_id();
		$post_b = static::factory()->post->create( [ 'post_type' => 'test-child-post' ] );
		update_post_meta( $post_a, 'testable_post_id', $post_b );

		$object = Testable_Post::find( $post_a );
		$first = $object->child()->first();

		$this->assertEquals( $post_b, $first->id() );
	}

	public function test_get_belongs_to() {
		$post_a = $this->get_random_post_id();
		$post_b = static::factory()->post->create( [ 'post_type' => 'test-child-post' ] );
		update_post_meta( $post_a, 'testable_post_id', $post_b );

		$object = Testable_Child_Post::find( $post_b );

		$first = $object->parent_item()->first();

		$this->assertEquals( $post_a, $first->id() );
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

	public function child() {
		return $this->has_one( Testable_Child_Post::class );
		return $this->has_one( Testable_Child_Post::class, 'testable_post_id' );
	}
}

class Testable_Child_Post extends Post {
	use Relationships;

	public static $object_name = 'test-child-post';

	public function parent_item() {
		return $this->belongs_to( Testable_Post::class );
		return $this->belongs_to( Testable_Post::class,  'testable_post_id' );
	}
}
