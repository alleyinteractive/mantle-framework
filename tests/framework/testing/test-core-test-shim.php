<?php
namespace Mantle\Framework\Testing;

class Test_Core_Test_Shim extends Test_Case {
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
			$this->assertInternalType( 'int', $post_id );
		}

		$this->assertEquals( 'draft', get_post_status( array_shift( $posts ) ) );
	}

	public function test_term_factory() {
		$this->assertInstanceOf( \WP_Term::class, static::factory()->category->create_and_get() );

		$terms = static::factory()->tag->create_many( 10 );
		foreach ( $terms as $term_id ) {
			$this->assertInternalType( 'int', $term_id );
		}

		$this->assertCount( 10, $terms );
	}
}
