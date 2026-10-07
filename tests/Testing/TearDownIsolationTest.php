<?php
namespace Mantle\Tests\Testing;

use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Ensure that a throwing trait teardown (such as an unexpected incorrect usage
 * notice) does not skip the remaining per-test cleanup.
 */
#[Group( 'testing' )]
class TearDownIsolationTest extends FrameworkTestCase {
	protected bool $torn_down = false;

	protected function tearDown(): void {
		if ( $this->torn_down ) {
			return;
		}

		$this->torn_down = true;

		parent::tearDown();
	}

	public function test_cleanup_runs_when_a_trait_teardown_throws(): void {
		add_filter( 'pre_option_blogname', fn () => 'leaked' );

		$this->acting_as( 'administrator' );

		$this->assertSame( 'leaked', get_option( 'blogname' ) );
		$this->assertNotSame( 0, get_current_user_id() );

		_doing_it_wrong( 'unexpected_usage_for_teardown_test', 'This is a test', '1.0.0' );

		$caught = null;

		try {
			$this->tearDown();
		} catch ( \Throwable $e ) {
			$caught = $e;
		}

		$this->assertNotNull( $caught, 'The unexpected incorrect usage should still be reported.' );
		$this->assertNotSame( 'leaked', get_option( 'blogname' ) );
		$this->assertSame( 0, get_current_user_id() );
	}
}
