<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class InteractsWithCronTest extends FrameworkTestCase {
	public function test_cron_event_count_assertions() {
		for ( $i = 0; $i < 5; $i++ ) {
			wp_schedule_single_event( time() + $i, 'example_hook', [ $i ] );
		}

		$this->assertCronCount( 'example_hook', 5 );
	}

	public function test_dispatch_cron(): void {
		$_SERVER['__test_dispatch_cron'] = 0;

		add_action( 'example_cron', fn () => $_SERVER['__test_dispatch_cron']++ );

		for ( $i = 1; $i < 4; $i++ ) {
			$this->assertTrue(
				wp_schedule_single_event( time() - $i, 'example_cron', [ $i ], true ),
			);
		}

		// Schedule one for the future.
		wp_schedule_single_event( time() + 60, 'example_cron', [ 'future' ], true );

		$this->assertInCronQueue( 'example_cron' );
		$this->assertInCronQueue( 'example_cron', [ 1 ] );
		$this->assertInCronQueue( 'example_cron', [ 2 ] );
		$this->assertCronCount( 'example_cron', 4 );

		$this->dispatch_cron( 'example_cron' );

		// Ensure one job is left (the future one).
		$this->assertInCronQueue( 'example_cron' );
		$this->assertInCronQueue( 'example_cron', [ 'future' ] );
		$this->assertCronCount( 'example_cron', 1 );

		// Ensure the callback was applied.
		$this->assertEquals( 3, $_SERVER['__test_dispatch_cron'] );

		$this->dispatch_cron( 'example_cron', future: true );

		// Ensure the future job was executed.
		$this->assertEquals( 4, $_SERVER['__test_dispatch_cron'] );
		$this->assertCronNotScheduled( 'example_cron' );
	}
}
