<?php

namespace Mantle\Tests\Database\Factory;

use Mantle\Database\Factory;
use Mantle\Database\Factory\Post_Factory;
use Mantle\Database\Model;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\DataProvider;
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
	}
}
