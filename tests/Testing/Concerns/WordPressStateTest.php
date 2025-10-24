<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Support\Helpers\dd_backtrace;

/**
 * @group testing
 */
#[Group( 'testing' )]
class WordPressStateTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();

		dd_backtrace();
	}

	public function test_set_posts_on_front(): void {
		// $this->
	}
}
