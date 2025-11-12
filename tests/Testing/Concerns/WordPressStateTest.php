<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Support\Helpers\collect;

/**
 * @group testing
 */
#[Group( 'testing' )]
class WordPressStateTest extends FrameworkTestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Register a meta key to test that it persists between tests.
		register_meta( 'post', 'example_meta_key', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		] );
	}
	/**
	 * Ensure that meta keys registered in one test are still registered in another.
	 *
	 * Previously, there was an issue where the registered meta keys would be cleared
	 * between tests, causing tests that relied on them to fail. This test ensures that
	 * the registered meta keys persist between tests.
	 *
	 * @dataProvider dataprovider_twice
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_meta_keys_preserved_between_tests(): void {
		$this->assertTrue( registered_meta_key_exists( 'post', 'example_meta_key' ) );
	}

	public function test_show_posts_on_frontpage_by_default(): void {
		$posts = collect( static::factory()->post->create_ordered_set_and_get( 3 ) );

		$this->get( '/' )
			->assertOk()
			->assertQueryTrue( 'is_home', 'is_front_page' )
			->assertQueriedObject( null )
			->assertQueriedObjectNull()
			->assertSeeInOrder( $posts->reverse()->values()->pluck( 'post_title' )->all() );
	}

	public function test_show_page_on_frontpage(): void {
		$front_page = static::factory()->page->create_and_get( [
			'post_title' => 'Front Page',
		] );

		$posts_page = static::factory()->page->create_and_get( [
			'post_title' => 'Posts Page',
		] );

		$posts = collect( static::factory()->post->create_ordered_set_and_get( 3 ) );

		$this->set_show_page_on_front( front: $front_page, posts: $posts_page );
		$this->get( '/' )
			->assertOk()
			->assertQueryTrue( 'is_front_page', 'is_page', 'is_singular' )
			->assertQueriedObject( $front_page )
			->assertSee( 'Front Page' )
			// Ensure posts are not shown on the front page.
			->assertDontSee( $posts->first()->post_title );

		// Ensure the posts page shows the posts.
		$this->get( get_permalink( $posts_page ) )
			->assertOk()
			->assertQueryTrue( 'is_home' )
			->assertQueriedObject( $posts_page )
			->assertSeeInOrder( $posts->reverse()->values()->pluck( 'post_title' )->all() );

		$this->assertTrue( $GLOBALS['wp_query']->is_posts_page );
	}

	/**
	 * Data provider that can be used to test that a data provider can be called
	 * multiple times without issue.
	 */
	public static function dataprovider_twice(): array {
		return [
			'first'  => [],
			'second' => [],
		];
	}
}
