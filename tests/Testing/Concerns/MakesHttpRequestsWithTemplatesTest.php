<?php
namespace Mantle\Tests\Testing\Concerns;

use Closure;
use Mantle\Testing\Concerns\Reset_Server;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

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

	public function test_ensure_all_requests_have_header_and_footer(): void {
		$this->iterate_test( function (): void {
			$this->get( '/' )
				->assertOk()
				->assertQuerySelectorExists( 'html' )
				->assertQuerySelectorExists( 'head' )
				->assertQuerySelectorExists( 'body' );
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

		$this->iterate_test( function (): void {
			$this->get( '/' )
				->assertOk()
				->assertQuerySelectorExists( 'html' )
				->assertQuerySelectorExists( 'body' )
				->assertElementExistsById( 'test-script-js' )
				->assertSee( 'console.log("inline-script-test");' );
		} );
	}

	protected function iterate_test( Closure $callback, int $times = 3 ): void {
		for ( $i = 0; $i < $times; $i += 1 ) {
			try {
				$callback( $i );
			} catch ( \Exception $e ) {
				// Wrap the exception with a more descriptive message about the
				// iteration of the test.
				throw new \PHPUnit\Framework\AssertionFailedError(
					'Failed on iteration ' . $i . ': ' . $e->getMessage(),
					0,
					$e
				);
			}
		}
	}
}
