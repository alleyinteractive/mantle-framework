<?php
namespace Mantle\Tests\Testing;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Utils;

class PermalinkStructureTest extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// Set the site's timezone.
		update_option( 'timezone_string', 'America/New_York' );
	}

	public function test_default_permalink_structure() {
		$this->assertEquals(
			Utils::DEFAULT_PERMALINK_STRUCTURE,
			get_option( 'permalink_structure' ),
		);

		$post = static::factory()->post->create_and_get( [
			'post_date' => '2018-01-01 00:00:00',
			'post_name' => 'test-post',
		] );

		$permalink = get_permalink( $post );

		$this->assertEquals( home_url( '/2018/01/01/test-post/' ), $permalink );

		// Ensure the permalink can be reached by the testing framework.
		$this->get( $permalink )
			->assertOk()
			->assertQueriedObject( $post )
			->assertQueryTrue( 'is_single', 'is_singular' );
	}

	public function test_custom_permalink_structure() {
		$this->set_permalink_structure( '/blog/%year%/%postname%/' );

		$this->assertEquals(
			'/blog/%year%/%postname%/',
			get_option( 'permalink_structure' ),
		);

	$post = static::factory()->post->create_and_get( [
			'post_date' => '2018-01-01 00:00:00',
			'post_name' => 'test-post-custom',
		] );

		$permalink = get_permalink( $post );

		$this->assertEquals( home_url( '/blog/2018/test-post-custom/' ), $permalink );

		// Ensure the permalink can be reached by the testing framework.
		$this->get( $permalink )
			->assertOk()
			->assertQueriedObject( $post )
			->assertQueryTrue( 'is_single', 'is_singular' );
	}

	public function test_no_permalinks() {
		$this->set_permalink_structure( '' );

		$this->assertEmpty( get_option( 'permalink_structure' ) );

		$post_id = static::factory()->post->create();

		$permalink = get_permalink( $post_id );

		$this->assertEquals( home_url( '?p=' . $post_id ), $permalink );

		// Ensure the permalink can be reached by the testing framework.
		$this->get( $permalink )
			->assertOk()
			->assertQueriedObjectId( $post_id )
			->assertQueryTrue( 'is_single', 'is_singular' );
	}
}
