<?php

namespace Mantle\Tests\Database\Factory;

use Byline_Manager\Models\Profile;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;
use stdClass;
use WP_Post;

/**
 * @group factory
 */
#[Group( 'factory' )]
class BylineManagerFactoryTest extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( Profile::class ) ) {
			$this->markTestSkipped( 'Byline Manager is not installed.' );
		}
	}

	public function test_create_profile(): void {
		$profile = static::factory()->byline_manager_profile->create_and_get();

		$this->assertInstanceOf( Profile::class, $profile );
		$this->assertNotEmpty( $profile->post->post_title );
		$this->assertNotEmpty( get_post_meta( $profile->post->ID, 'first_name', true ) );
		$this->assertNotEmpty( get_post_meta( $profile->post->ID, 'last_name', true ) );
	}

	public function test_create_guest_author_with_display_name(): void {
		$author = static::factory()->byline_manager_profile->create_and_get( [
			'display_name' => 'John Doe',
		] );

		$this->assertInstanceOf( Profile::class, $author );
		$this->assertSame( 'John Doe', $author->post->post_title );
	}

	public function test_create_guest_author_linked(): void {
		$user   = static::factory()->user->create_and_get();
		$author = static::factory()->byline_manager_profile->with_linked_user( $user->ID )->create_and_get();

		$this->assertInstanceOf( Profile::class, $author );

		$this->assertEquals( $user->user_login, $author->post->post_name );
		$this->assertEquals( $user->ID, get_post_meta( $author->post->ID, 'user_id', true ) );
	}

	// public function test_create_post_with_guest_author(): void {
	// 	$author   = static::factory()->cap_guest_author->create_and_get();
	// 	$author_2 = static::factory()->cap_guest_author->create_and_get();
	// 	$post   = static::factory()->post->with_cap_authors( $author->ID, $author_2 )->create_and_get();

	// 	$this->assertInstanceOf( WP_Post::class, $post );

	// 	$guest_authors = get_coauthors( $post->ID );

	// 	$this->assertCount( 2, $guest_authors );
	// 	$this->assertEquals(
	// 		collect( [ $author, $author_2 ] )->pluck( 'display_name' )->sort()->all(),
	// 		collect( $guest_authors )->pluck( 'display_name' )->sort()->all(),
	// 	);
	// }

	// public function test_create_post_with_guest_author_and_user(): void {
	// 	$user   = static::factory()->user->create_and_get();
	// 	$author = static::factory()->cap_guest_author->create_and_get();

	// 	$post = static::factory()->post->with_cap_authors( $user, $author )->create_and_get();

	// 	$guest_authors = get_coauthors( $post->ID );

	// 	$this->assertCount( 2, $guest_authors );
	// 	$this->assertEquals(
	// 		collect( [ $author->display_name, $user->display_name ] )->sort()->all(),
	// 		collect( $guest_authors )->pluck( 'display_name' )->sort()->all(),
	// 	);
	// }
}
