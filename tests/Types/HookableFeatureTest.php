<?php
namespace Mantle\Tests\Types;

use Mantle\Support\Attributes\Action;
use Mantle\Types\Hookable_Feature;
use PHPUnit\Framework\TestCase;

class HookableFeatureTest extends TestCase {
	public function test_hookable_feature_registers_hooks_on_boot(): void {
		$_SERVER['__hook_fired'] = false;

		$feature = new class extends Hookable_Feature {
			#[Action( 'example_action' )]
			public function example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};

		$this->assertFalse( $_SERVER['__hook_fired'] );

		$feature->boot();

		do_action( 'example_action', 'foo' );

		$this->assertSame( 'foo', $_SERVER['__hook_fired'] );
	}
}
