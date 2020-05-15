<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Framework\Database\Model\Comment;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Query\Builder;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Comment_Object extends WP_UnitTestCase {
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
	}

	protected function get_random_post_id(): int {
		$post_ids = static::factory()->post->create_many( 10 );
		return $post_ids[ array_rand( $post_ids ) ];
	}
}

class Testable_Post extends Post {
	public static $object_name = 'post';
}
