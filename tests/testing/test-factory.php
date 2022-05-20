<?php
namespace Mantle\Testing;

use Carbon\Carbon;

use function Mantle\Support\Helpers\collect;

class Test_Factory extends Framework_Test_Case {
	public function test_post_factory() {
		$this->assertInstanceOf( \WP_Post::class, static::factory()->post->create_and_get() );

		$posts = static::factory()->post->create_many(
			10,
			[
				'post_type'   => 'post',
				'post_status' => 'draft',
			]
		);

		$this->assertCount( 10, $posts );
		foreach ( $posts as $post_id ) {
			$this->assertIsInt( $post_id );
		}

		$this->assertEquals( 'draft', get_post_status( array_shift( $posts ) ) );
	}

	public function test_post_with_thumbnail_factory() {
		$post_id = static::factory()->post->create_with_thumbnail();

		$this->assertTrue( has_post_thumbnail( $post_id ) );
	}

	public function test_post_descending_set() {
		$post_ids = static::factory()->post->create_ordered_set( 10, [
			'meta' => [
				'_test_date_meta_key' => '_test_meta_value',
			],
		] );

		$this->assertCount( 10, $post_ids );

		$dates = collect( $post_ids )
			->map( fn ( $post_id ) => Carbon::parse( get_post( $post_id )->post_date ) )
			->to_array();

		foreach ( $dates as $i => $date ) {
			if ( isset( $dates[ $i - 1 ] ) ) {
				$this->assertEquals(
					3600,
					$date->diffInSeconds( $dates[ $i - 1 ] ),
					'Distance between posts not expected 3600 seconds',
				);
			}
		}

		// Query the posts and ensure the order matches.
		$queried_post_ids = get_posts( [
			'fields'           => 'ids',
			'meta_key'         => '_test_date_meta_key',
			'meta_value'       => '_test_meta_value',
			'order'            => 'DESC',
			'orderby'          => 'post_date',
			'posts_per_page'   => 50,
			'suppress_filters' => false,
		] );

		$this->assertCount( 10, $queried_post_ids );

		// Posts should be in the opposite order since we're sorting by descending date.
		$this->assertEquals( array_reverse( $post_ids ), $queried_post_ids );
	}

	public function test_attachment_factory() {
		$this->shim_test( \WP_Post::class, 'attachment' );

		$attachment = static::factory()->attachment->create_and_get();
		$this->assertEquals( 'attachment', get_post_type( $attachment ) );
	}

	public function test_term_factory() {
		$this->shim_test( \WP_Term::class, 'category' );
		$this->shim_test( \WP_Term::class, 'tag' );
	}

	public function test_blog_factory() {
		$this->shim_test( \WP_Site::class, 'blog' );
	}

	public function test_network_factory() {
		$this->shim_test( \WP_Network::class, 'network' );
	}

	public function test_user_factory() {
		$this->shim_test( \WP_User::class, 'user' );
	}

	public function test_comment_factory() {
		$this->shim_test( \WP_Comment::class, 'comment' );
	}

	protected function shim_test( string $class_name, string $property ) {
		$this->assertInstanceOf( $class_name, static::factory()->$property->create_and_get() );

		$object_ids = static::factory()->$property->create_many( 10 );
		foreach ( $object_ids as $object_id ) {
			$this->assertIsInt( $object_id );
		}

		$this->assertCount( 10, $object_ids );
	}
}
