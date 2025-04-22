<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\Attributes\Acting_As;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class AssertionsTest extends FrameworkTestCase {
	public function test_assert_wp_error(): void {
		$this->assertWpError( new \WP_Error( 'error_code', 'Error message' ) );
		$this->assertNotWpError( 'not an error' );
	}

	public function test_assert_wp_post(): void {
		$this->assertWpPost( static::factory()->post->create_and_get() );
	}

	public function test_assert_wp_term(): void {
		$this->assertWpTerm( static::factory()->term->create_and_get() );
	}

	public function test_assert_wp_user(): void {
		$this->assertWpUser( static::factory()->user->create_and_get() );
	}

	public function test_query_true(): void {
		$this->get( '/' )->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->get( static::factory()->post->create_and_get() )->assertQueryTrue( 'is_single', 'is_singular' );
	}

	public function test_queried_object(): void {
		$post = static::factory()->post->create_and_get();

		$this->get( $post )->assertQueriedObject( $post )->assertQueriedObjectId( $post->ID );

		$this->get( '/' )->assertQueriedObjectNull();
	}

	public function test_assert_post_exists(): void {
		static::factory()->post->with_meta( 'example', 'key' )->create_many( 23 );

		$this->assertPostExists( [
			'meta_key'   => 'example',
			'meta_value' => 'key',
		] );

		$this->assertPostExists( [
			'meta_key'   => 'example',
			'meta_value' => 'key',
		], count: 23 );
	}

	public function test_assert_term_exists(): void {
		static::factory()->tag->create( [ 'name' => 'example' ] );

		$this->assertTermExists( [
			'name' => 'example',
		] );
	}

	public function test_assert_user_exists(): void {
		static::factory()->user->create( [ 'user_login' => 'example' ] );

		$this->assertUserExists( [
			'user_login' => 'example',
		] );
	}

	public function test_assert_post_has_term(): void {
		$post = static::factory()->post->with_terms( $tag = static::factory()->tag->create_and_get() )->create_and_get();

		$this->assertPostHasTerm( $post, $tag );

		$post = static::factory()->post->create_and_get();

		$this->assertPostNotHasTerm( $post, $tag );
	}
}
