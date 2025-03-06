<?php

namespace Mantle\Tests\Database\Factory;

use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;
use stdClass;
use WP_Post;

/**
 * @group factory
 */
#[Group( 'factory' )]
class CoAuthorsPlusFactoryTest extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( \CoAuthors_Guest_Authors::class ) ) {
			$this->markTestSkipped( 'Co-Authors Plus is not installed.' );
		}
	}

	public function test_create_guest_author(): void {
		$author = static::factory()->cap_guest_author->create_and_get();

		$this->assertInstanceOf( stdClass::class, $author );
		$this->assertNotEmpty( $author->display_name );
		$this->assertNotEmpty( get_post_meta( $author->ID, 'cap-first_name', true ) );
		$this->assertNotEmpty( get_post_meta( $author->ID, 'cap-last_name', true ) );
	}

	public function test_create_guest_author_with_display_name(): void {
		$author = static::factory()->cap_guest_author->create_and_get( [
			'display_name' => 'John Doe',
		] );

		$this->assertInstanceOf( stdClass::class, $author );
		$this->assertSame( 'John Doe', $author->display_name );
		$this->assertSame( 'cap-john-doe', $author->user_login );
		$this->assertEquals( 'John', get_post_meta( $author->ID, 'cap-first_name', true ) );
		$this->assertEquals( 'Doe', get_post_meta( $author->ID, 'cap-last_name', true ) );
	}

	public function test_create_guest_author_linked(): void {
		$user   = static::factory()->user->create_and_get();
		$author = static::factory()->cap_guest_author->with_linked_user( $user->ID )->create_and_get();

		$this->assertInstanceOf( stdClass::class, $author );
		$this->assertEquals( $user->user_login, get_post_meta( $author->ID, 'cap-user_login', true ) );

		$this->assertEquals(
			get_post_meta( $author->ID, 'cap-first_name', true ),
			get_user_meta( $user->ID, 'first_name', true )
		);

		$this->assertEquals(
			get_post_meta( $author->ID, 'cap-last_name', true ),
			get_user_meta( $user->ID, 'last_name', true )
		);

		$this->assertEquals(
			get_post_meta( $author->ID, 'cap-display_name', true ),
			$user->display_name,
		);

		$this->assertEquals(
			get_post_meta( $author->ID, 'cap-user_email', true ),
			$user->user_email,
		);
	}

	public function test_create_post_with_guest_author(): void {
		$author   = static::factory()->cap_guest_author->create_and_get();
		$author_2 = static::factory()->cap_guest_author->create_and_get();
		$post   = static::factory()->post->with_cap_authors( $author->ID, $author_2 )->create_and_get();

		$this->assertInstanceOf( WP_Post::class, $post );

		$guest_authors = get_coauthors( $post->ID );

		$this->assertCount( 2, $guest_authors );
		$this->assertEquals(
			collect( [ $author, $author_2 ] )->pluck( 'display_name' )->sort()->all(),
			collect( $guest_authors )->pluck( 'display_name' )->sort()->all(),
		);
	}

	public function test_create_post_with_guest_author_and_user(): void {
		$user   = static::factory()->user->create_and_get();
		$author = static::factory()->cap_guest_author->create_and_get();

		$post = static::factory()->post->with_cap_authors( $user, $author )->create_and_get();

		$guest_authors = get_coauthors( $post->ID );

		$this->assertCount( 2, $guest_authors );
		$this->assertEquals(
			collect( [ $author->display_name, $user->display_name ] )->sort()->all(),
			collect( $guest_authors )->pluck( 'display_name' )->sort()->all(),
		);
	}
}
