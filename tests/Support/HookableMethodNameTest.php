<?php

namespace Mantle\Tests\Support;

use Mantle\Support\Traits\Hookable;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Tests\Support\Concerns\TestValidatorAttribute;
use PHPUnit\Framework\Attributes\Group;

#[Group('hookable')]
class HookableMethodNameTest extends FrameworkTestCase {
	public function setUp(): void {
		parent::setUp();

		remove_all_actions( 'example_action' );

		$_SERVER['__hook_fired'] = [];
	}

	protected function tearDown(): void {
		parent::tearDown();

		putenv( 'WP_ENVIRONMENT_TYPE=' );
	}

	public function test_action(): void {
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

	public function test_action_with_priority(): void {

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

	public function test_action_with_inactive_validator_attribute(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[TestValidatorAttribute( false )]
			public function action__example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};

		new $class;

		$this->assertFalse( $_SERVER['__hook_fired'] );

		do_action( 'example_action', 'foo' );

		$this->assertFalse( $_SERVER['__hook_fired'] );
	}

	public function test_action_with_active_validator_attribute(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[TestValidatorAttribute( true )]
			public function action__example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};

		new $class;

		$this->assertFalse( $_SERVER['__hook_fired'] );

		do_action( 'example_action', 'foo' );

		$this->assertSame( 'foo', $_SERVER['__hook_fired'] );
	}

	public function test_filter(): void {
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

	public function test_filter_with_priority(): void {
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

	public function test_throws_doing_it_wrong_if_method_is_not_public(): void {
		putenv( 'WP_ENVIRONMENT_TYPE=production' );

		$class = new class {
			use Hookable;

			private function action__example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};

		$this->setExpectedIncorrectUsage( $class::class . '::action__example_action' );

		remove_all_actions( 'example_action' );

		new $class;
	}

	public function test_throws_exception_if_method_is_not_callable(): void {
		putenv( 'WP_ENVIRONMENT_TYPE=local' );

		$this->expectException( \RuntimeException::class );

		$class = new class {
			use Hookable;

			private function action__example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};
	}
}
