<?php
namespace Mantle\Tests\Testing;

use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Group;

/**
 * A test skipped by a #[Before] hook must be reported as skipped, not as an
 * error. PHPUnit 12.5.40 and later do not call setUp() for it, but still call
 * tearDown().
 */
#[Group( 'testing' )]
class SkippedBeforeHookTest extends FrameworkTestCase {
	#[Before]
	public function skip_the_test(): void {
		$this->markTestSkipped( 'Skipped on purpose by a before hook.' );
	}

	public function test_skipped_before_hook_does_not_error(): void {
		$this->fail( 'This test should have been skipped.' );
	}
}
