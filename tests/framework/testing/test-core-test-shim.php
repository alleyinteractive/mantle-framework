<?php
namespace Mantle\Framework\Testing;

class Test_Core_Test_Shim extends Framework_Test_Case {
	public function test_go_to() {
		$this->go_to( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}

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
