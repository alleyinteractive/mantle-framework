<?php
namespace Mantle\Tests\Types;

use Attribute;
use Mantle\Types\Validator;
use Mantle\Types\Validator_Group;
use PHPUnit\Framework\TestCase;

class ValidatorGroupTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		remove_all_actions( 'example_action' );
	}

	public function test_boot_feature_without_validator(): void {
		$feature = new class implements \Alley\WP\Types\Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		$group = new Validator_Group( $feature );

		$this->assertFalse( $_SERVER['feature_booted'] );

		$group->boot();

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_boot_feature_with_failing_validator(): void {
		$feature = new #[Failing_Attribute] class implements \Alley\WP\Types\Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		$group = new Validator_Group( $feature );

		$this->assertFalse( $_SERVER['feature_booted'] );

		$group->boot();

		$this->assertFalse( $_SERVER['feature_booted'] );
	}

	public function test_boot_feature_with_passing_validator(): void {
		$feature = new #[Passing_Attribute] class implements \Alley\WP\Types\Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		$group = new Validator_Group( $feature );

		$this->assertFalse( $_SERVER['feature_booted'] );

		$group->boot();

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_boot_nested_feature(): void {
		$group = new Validator_Group(
			new Validator_Group(
				new #[Passing_Attribute] class implements \Alley\WP\Types\Feature {
					public function boot(): void {
						$_SERVER['feature_booted'] = true;
					}
				}
			)
		);

		$_SERVER['feature_booted'] = false;
		$this->assertFalse( $_SERVER['feature_booted'] );

		$group->boot();

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_throws_exception_for_invalid_feature(): void {
		$this->expectException( \InvalidArgumentException::class );

		$group = new Validator_Group( new \stdClass() );
		$group->boot();
	}

	public function test_throws_exception_for_invalid_feature_in_array(): void {
		$this->expectException( \InvalidArgumentException::class );

		$group = new Validator_Group( [ new \stdClass() ] );
		$group->boot();
	}

	public function test_boot_closure_feature(): void {
		$_SERVER['feature_booted'] = false;

		$group = new Validator_Group(
			function() {
				$_SERVER['feature_booted'] = true;
			}
		);

		$this->assertFalse( $_SERVER['feature_booted'] );

		$group->boot();

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_hookable_feature_booted_if_validator_passes(): void {
		$feature = new #[Passing_Attribute] class extends \Mantle\Types\Hookable_Feature {
			#[\Mantle\Support\Attributes\Action( 'example_action' )]
			public function example_action( mixed $args ): void {
				$_SERVER['hook_fired'] = $args;
			}
		};

		$group = new Validator_Group( $feature );

		$_SERVER['hook_fired'] = false;

		$group->boot();

		do_action( 'example_action', 'foo' );

		$this->assertSame( 'foo', $_SERVER['hook_fired'] );
	}

	public function test_hookable_feature_not_booted_if_validator_fails(): void {
		$feature = new #[Failing_Attribute] class extends \Mantle\Types\Hookable_Feature {
			#[\Mantle\Support\Attributes\Action( 'example_action' )]
			public function example_action( mixed $args ): void {
				$_SERVER['hook_fired'] = $args;
			}
		};

		$group = new Validator_Group( $feature );

		$_SERVER['hook_fired'] = false;

		$group->boot();

		do_action( 'example_action', 'foo' );

		$this->assertFalse( $_SERVER['hook_fired'] );
	}
}

#[Attribute]
class Failing_Attribute implements Validator {
	public function validate(): bool {
		return false;
	}
}

#[Attribute]
class Passing_Attribute implements Validator {
	public function validate(): bool {
		return true;
	}
}
