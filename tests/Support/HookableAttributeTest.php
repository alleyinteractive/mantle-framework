<?php

namespace Mantle\Tests\Support;

use Mantle\Support\Attributes\Action;
use Mantle\Support\Attributes\Filter;
use Mantle\Support\Traits\Hookable;
use PHPUnit\Framework\TestCase;

class HookableAttributeTest extends TestCase {
	public function setUp(): void {
		parent::setUp();

		remove_all_actions( 'example_action' );
	}

	public function test_action_from_method_name(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[Action( 'example_action' )]
			public function example_action( mixed $args ): void {
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

			#[Action( 'example_action', 20 )]
			public function action_at_20( mixed $args ): void {
				$_SERVER['__hook_fired'][] = 20;
			}

			#[Action( 'example_action' )]
			public function action_at_10( mixed $args ): void {
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

			#[Filter( 'example_action' )]
			public function filter_the_value( mixed $value ): mixed {
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

			#[Filter( 'example_action', priority: 20 )]
			public function filter_at_20( int $value ): int {
				$_SERVER['__hook_fired'][] = $value;

				return $value + 20;
			}

			#[Filter( 'example_action' )]
			public function filter_at_10( int $value ): int {
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

	public function test_multiple_filters_on_one_method(): void {
		$_SERVER['__hook_fired'] = [];

		$class = new class {
			use Hookable;

			#[Filter( 'another_filter' )]
			#[Filter( 'example_action' )]
			public function filter_to_call( int $value ): int {
				$_SERVER['__hook_fired'][] = $value;

				return $value + 20;
			}
		};

		// Remove the action that was added by creating the anonymous class.
		remove_all_actions( 'another_filter' );
		remove_all_actions( 'example_action' );

		new $class;

		$this->assertTrue( has_filter( 'another_filter' ) );
		$this->assertTrue( has_filter( 'example_action' ) );

		$this->assertEmpty( $_SERVER['__hook_fired'] );

		$value = apply_filters( 'example_action', 5 );

		$this->assertSame( [ 5 ], $_SERVER['__hook_fired'] );
		$this->assertSame( 25, $value );

		$_SERVER['__hook_fired'] = [];

		$value = apply_filters( 'another_filter', 10 );

		$this->assertSame( [ 10 ], $_SERVER['__hook_fired'] );
		$this->assertSame( 30, $value );
	}
}
