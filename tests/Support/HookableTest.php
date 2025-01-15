<?php

namespace Mantle\Tests\Support;

use Mantle\Support\Traits\Hookable;
use PHPUnit\Framework\TestCase;

class HookableTest extends TestCase {
	public function setUp(): void {
		parent::setUp();

		remove_all_actions( 'example_action' );
	}

	public function test_action_from_method_name(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			public function action__example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};

		new $class;

		$this->assertFalse( $_SERVER['__hook_fired'] );

		do_action( 'example_action', 'foo' );

		$this->assertSame( 'foo', $_SERVER['__hook_fired'] );
	}

	public function test_action_from_method_name_with_priority(): void {

		$_SERVER['__hook_fired'] = [];

		$class = new class {
			use Hookable;

			public function action__example_action_at_20( mixed $args ): void {
				$_SERVER['__hook_fired'][] = 20;
			}

			public function action__example_action_at_10( mixed $args ): void {
				$_SERVER['__hook_fired'][] = 10;
			}
		};

		// Remove the action that was added by creating the anonymous class.
		remove_all_actions( 'example_action' );

		new $class;

		$this->assertEmpty( $_SERVER['__hook_fired'] );

		do_action( 'example_action', 'foo' );

		$this->assertSame( [ 10, 20 ], $_SERVER['__hook_fired'] );
	}

	public function test_filter_from_method_name(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			public function filter__example_action( mixed $value ): mixed {
				$_SERVER['__hook_fired'] = $value;

				return 'bar';
			}
		};

		remove_all_filters( 'example_action' );

		new $class;

		$this->assertFalse( $_SERVER['__hook_fired'] );

		$value = apply_filters( 'example_action', 'foo' );

		$this->assertSame( 'foo', $_SERVER['__hook_fired'] );
		$this->assertSame( 'bar', $value );
	}

	public function test_filter_from_method_name_with_priority(): void {
		$_SERVER['__hook_fired'] = [];

		$class = new class {
			use Hookable;

			public function filter__example_action_at_20( int $value ): int {
				$_SERVER['__hook_fired'][] = $value;

				return $value + 20;
			}

			public function filter__example_action_at_10( int $value ): int {
				$_SERVER['__hook_fired'][] = $value;

				return $value + 10;
			}
		};

		// Remove the action that was added by creating the anonymous class.
		remove_all_actions( 'example_action' );

		new $class;

		$this->assertEmpty( $_SERVER['__hook_fired'] );

		$value = apply_filters( 'example_action', 5 );

		$this->assertSame( [ 5, 15 ], $_SERVER['__hook_fired'] );
		$this->assertSame( 35, $value );
	}
}
