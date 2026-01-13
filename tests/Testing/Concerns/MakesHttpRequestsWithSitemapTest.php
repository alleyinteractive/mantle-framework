<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Support\Str;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Concerns\Reset_Server;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\retry;

/**
 * Tests for making internal HTTP requests in tests related to sitemaps and robots.txt.
 *
 * @see \Mantle\Testing\Concerns\Makes_Http_Requests_With_Sitemaps
 * @group testing
 */
#[Group( 'testing' )]
class MakesHttpRequestsWithSitemapTest extends FrameworkTestCase {
	use Refresh_Database;
	use Reset_Server;

	protected function setUp(): void {
		parent::setUp();

		$this->flush_default_headers();
	}

	/**
	 * Test a request to robots.txt.
	 *
	 * We aren't able to check the header because do_robots() calls `header()`
	 * itself instead of using the proper `wp_headers` hook.
	 */
	public function test_robots_txt(): void {
		$this
			->get( '/robots.txt' )
			->assertOk()
			->assertSee( 'User-agent: *' )
			->assertSee( 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) );
	}

	/**
	 * @dataProvider dataprovider_run_test_multiple_times
	 */
	#[DataProvider( 'dataprovider_run_test_multiple_times' )]
	public function test_sitemap( $run = 0 ): void {
		$post = static::factory()->post->create_and_get( [
			'post_title' => 'Example Post for Sitemap',
			'post_name'  => 'example-post-for-sitemap',
		] );

		$server = wp_sitemaps_get_server();

		$this->assertInstanceOf( \WP_Sitemaps::class, $server );
		$this->assertTrue( $server->sitemaps_enabled(), 'Expected sitemaps to be enabled.' );

		$sitemap_url = $server->index->get_index_url();

		$this->assertEquals( home_url( '/wp-sitemap.xml' ), $sitemap_url );

		$response = $this->get( $sitemap_url )
			->assertOk()
			->assertIsXml()
			->assertSee( '<sitemapindex ' )
			->assertSee( '<sitemap><loc>' );

		// Extract a sitemap URL from the index (it is surrounded by <loc> tags).
		preg_match( '/<loc>(.*?)<\/loc>/', $response->get_content(), $matches );

		$this->assertNotEmpty( $matches[1] ?? null, 'Expected to find a sitemap URL in the index.' );

		$this->assertTrue(
			Str::is( home_url( '/wp-sitemap-posts-post-*.xml' ), $matches[1] ),
		);

		$this->get( $matches[1] )
			->assertOk()
			->assertIsXml()
			->assertDontSee( '<sitemapindex ' )
			->assertSee( '<loc>' . get_permalink( $post ) . '</loc>' );
	}

	/**
	 * Data provider to run the multiple requests test several times.
	 */
	public static function dataprovider_run_test_multiple_times(): array {
		return collect( range( 1, 3 ) )
			->map_with_keys( fn ( $i ) => [ "run_{$i}" => [ $i ] ] )
			->all();
	}
}
