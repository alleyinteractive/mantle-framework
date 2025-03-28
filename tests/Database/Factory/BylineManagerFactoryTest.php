<?php

namespace Mantle\Tests\Database\Factory;

use Byline_Manager\Models\Profile;
use Byline_Manager\Utils;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;
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

	public function test_create_post_with_guest_author(): void {
		$author   = static::factory()->byline_manager_profile->create_and_get();
		$author_2 = static::factory()->byline_manager_profile->create_and_get();
		$post     = static::factory()->post->with_byline_manager_authors( $author->post->ID, $author_2 )->create_and_get();

		$this->assertInstanceOf( WP_Post::class, $post );

		$byline_entries = Utils::get_byline_entries_for_post( $post );

		$this->assertCount( 2, $byline_entries );
		$this->assertEquals(
			collect( [ $author, $author_2 ] )->pluck( 'post.post_title' )->all(),
			collect( $byline_entries )->pluck( 'post.post_title' )->all(),
		);
	}

	public function test_create_post_with_profile_and_user(): void {
		$author   = static::factory()->byline_manager_profile->create_and_get();
		$user     = static::factory()->user->create_and_get();
		$post     = static::factory()->post->with_byline_manager_authors( $author, $user )->create_and_get();

		$this->assertInstanceOf( WP_Post::class, $post );

		$byline_entries = Utils::get_byline_entries_for_post( $post );

		$this->assertCount( 2, $byline_entries );
		$this->assertEquals(
			[ $author->post->post_title, $user->display_name ],
			collect( $byline_entries )->pluck( 'post.post_title' )->all(),
		);
	}

	public function test_create_post_with_profile_and_text_byline(): void {
		$author   = static::factory()->byline_manager_profile->create_and_get();
		$post     = static::factory()->post->with_byline_manager_authors( $author, 'John Doe' )->create_and_get();

		$this->assertInstanceOf( WP_Post::class, $post );

		$byline_entries = Utils::get_byline_entries_for_post( $post );

		$this->assertCount( 2, $byline_entries );
		$this->assertEquals(
			[ $author->post->post_title, 'John Doe' ],
			collect( $byline_entries )->map(
				fn ( $entry ) => $entry instanceof Profile ? $entry->post->post_title : $entry->atts['text']
			)->all(),
		);
	}
}
