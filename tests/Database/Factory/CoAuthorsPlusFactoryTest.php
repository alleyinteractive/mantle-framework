<?php

namespace Mantle\Tests\Database\Factory;

use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;
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

		$this->assertInstanceOf( WP_Post::class, $author );
		$this->assertNotEmpty( $author->post_title );
		$this->assertNotEmpty( get_post_meta( $author->ID, 'cap-first_name', true ) );
		$this->assertNotEmpty( get_post_meta( $author->ID, 'cap-last_name', true ) );
	}

	public function test_create_guest_author_with_display_name(): void {
		$author = static::factory()->cap_guest_author->create_and_get( [
			'display_name' => 'John Doe',
		] );

		$this->assertInstanceOf( WP_Post::class, $author );
		$this->assertSame( 'John Doe', $author->post_title );
		$this->assertSame( 'cap-john-doe', $author->post_name );
		$this->assertEquals( 'John', get_post_meta( $author->ID, 'cap-first_name', true ) );
		$this->assertEquals( 'Doe', get_post_meta( $author->ID, 'cap-last_name', true ) );
	}

	public function test_create_guest_author_linked(): void {
		// TODO Improve user.
		$user   = static::factory()->user->create_and_get();
		$author = static::factory()->cap_guest_author->with_linked_user( $user->ID )->create_and_get();

		$this->assertInstanceOf( WP_Post::class, $author );
		$this->assertEquals( $user->user_login, get_post_meta( $author->ID, 'cap-user_login', true ) );

		// Compare the other fields.
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
}
