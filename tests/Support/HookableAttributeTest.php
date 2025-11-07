<?php

namespace Mantle\Tests\Support;

use Mantle\Support\Attributes\Action;
use Mantle\Support\Attributes\Filter;
use Mantle\Support\Attributes\Hookable\Allow_Legacy_Duplicate_Registration;
use Mantle\Support\Traits\Hookable;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Tests\Support\Concerns\TestValidatorAttribute;
use PHPUnit\Framework\Attributes\Group;

#[Group('hookable')]
class HookableAttributeTest extends FrameworkTestCase {
	protected function setUp(): void {
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

	public function test_action_with_priority(): void {
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

	public function test_action_with_valid_attributes_only(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[Action( 'example_action' )]
			public function action_at_20( mixed $args ): void {
				$_SERVER['__hook_fired'] = $this->get_number();
			}

			public function get_number(): string {
				return 'boo';
			}
		};

		new $class;

		$this->assertEmpty( $_SERVER['__hook_fired'] );

		do_action( 'example_action', 'foo' );

		$this->assertSame( 'boo', $_SERVER['__hook_fired'] );
	}

	public function test_action_with_inactive_validator_attribute(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[TestValidatorAttribute( false )]
			#[Action( 'example_action' )]
			public function example_action( mixed $args ): void {
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

	public function test_filter(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[Filter( 'example_action' )]
			public function filter_the_value( mixed $value ): string {
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

	public function test_filter_with_inactive_validator_attribute(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[TestValidatorAttribute( false )]
			#[Filter( 'example_action' )]
			public function filter_the_value( mixed $value ): string {
				$_SERVER['__hook_fired'] = $value;

				return 'bar';
			}
		};

		remove_all_filters( 'example_action' );

		new $class;

		$this->assertFalse( $_SERVER['__hook_fired'] );

		$value = apply_filters( 'example_action', 'foo' );

		$this->assertEmpty( $_SERVER['__hook_fired'] );
		$this->assertSame( 'foo', $value );
	}

	public function test_filter_with_active_validator_attribute(): void {
		$_SERVER['__hook_fired'] = false;

		$class = new class {
			use Hookable;

			#[TestValidatorAttribute( true )]
			#[Filter( 'example_action' )]
			public function filter_the_value( mixed $value ): string {
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

	/**
	 * @link https://github.com/alleyinteractive/mantle-framework/issues/657
	 */
	public function test_ignore_method_name_hook_registration_when_an_action_attribute_is_used(): void {
		$class = new class {
			use Hookable;

			#[Action( 'example_action', priority: 5 )]
			public function action__example_action( string $value ): void {
				$_SERVER['__hook_fired'][] = $value;
			}
		};

		// Remove the action that was added by creating the anonymous class.
		remove_all_actions( 'example_action' );

		new $class;

		$this->assertEmpty( $_SERVER['__hook_fired'] );

		do_action( 'example_action', 'foo' );

		$this->assertEquals( [ 'foo' ], $_SERVER['__hook_fired'] );
	}

	/**
	 * @link https://github.com/alleyinteractive/mantle-framework/issues/657
	 */
	public function test_allow_duplicate_method_name_hook_registration_when_legacy_attribute_is_used(): void {
		$class = new #[Allow_Legacy_Duplicate_Registration] class {
			use Hookable;

			#[Action( 'example_action', priority: 5 )]
			public function action__example_action( string $value ): void {
				$_SERVER['__hook_fired'][] = $value;
			}
		};

		// Remove the action that was added by creating the anonymous class.
		remove_all_actions( 'example_action' );

		new $class;

		$this->assertEmpty( $_SERVER['__hook_fired'] );

		do_action( 'example_action', 'foo' );

		$this->assertEquals( [ 'foo', 'foo' ], $_SERVER['__hook_fired'] );
	}

	public function test_throws_doing_it_wrong_if_method_is_not_public(): void {
		$class = new class {
			use Hookable;

			#[Action( 'example_action' )]
			private function example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};

		$this->setExpectedIncorrectUsage( $class::class . '::example_action' );

		remove_all_actions( 'example_action' );

		new $class;
	}

	public function test_throws_exception_if_method_is_not_callable(): void {
		putenv( 'WP_ENVIRONMENT_TYPE=local' );

		$this->expectException( \RuntimeException::class );

		$class = new class {
			use Hookable;

			#[Action( 'example_action' )]
			private function example_action( mixed $args ): void {
				$_SERVER['__hook_fired'] = $args;
			}
		};
	}
}
