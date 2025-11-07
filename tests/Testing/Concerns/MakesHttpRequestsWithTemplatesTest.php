<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Concerns\Reset_Server;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Testing\iterate_test;

/**
 * Tests for making HTTP requests in unit tests that relate to cleaning up
 * globals (such as enqueued scripts) and ensuring that requests have a
 * header/footer.
 *
 * @group testing
 */
#[Group( 'testing' )]
class MakesHttpRequestsWithTemplatesTest extends FrameworkTestCase {
	use Reset_Server;

	/**
	 * Ensure that all HTTP requests have a header and footer. Ensures that
	 * get_header() and get_footer() work properly.
	 */
	public function test_ensure_all_requests_have_header_and_footer(): void {
		iterate_test( function (): void {
			$this->get( '/' )
				->assertOk()
				->assertQuerySelectorExists( 'html', 1 )
				->assertQuerySelectorExists( 'head', 1 )
				->assertQuerySelectorExists( 'body', 1 );
		} );
	}

	/**
	 * Test that scripts that are registered at 'wp_enqueue_scripts' are
	 * always output when a HTTP request is made multiple times in the same test.
	 */
	public function test_scripts_are_always_output_when_called_multiple_times(): void {
		add_action( 'wp_enqueue_scripts', function (): void {
			wp_enqueue_script( 'test-script', 'https://example.com/test.js' );
			wp_add_inline_script( 'test-script', 'console.log("inline-script-test");' );
		} );

		iterate_test( function ( int $i ): void {
			$this->get( '/' )
				->assertOk()
				->assertQuerySelectorExists( 'html', 1 )
				->assertQuerySelectorExists( 'body', 1 )
				->assertElementExistsById( 'test-script-js' )
				->assertSee( 'console.log("inline-script-test");' );
		} );
	}
}
