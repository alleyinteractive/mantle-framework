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
}
