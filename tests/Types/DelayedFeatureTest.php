<?php
namespace Mantle\Tests\Types;

use Alley\WP\Types\Feature;
use Mantle\Types\Delayed_Feature;
use PHPUnit\Framework\TestCase;

class DelayedFeatureTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		$_SERVER['feature_booted'] = false;
		$_SERVER['hook_fired'] = false;
	}

	protected function tearDown(): void {
		parent::tearDown();

		remove_all_actions( 'init' );
		remove_all_actions( 'wp_loaded' );
		remove_all_actions( 'example_hook' );
	}

	public function test_delayed_feature_boots_on_hook_with_single_feature(): void {
		$feature = new class implements Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$delayed = new Delayed_Feature( 'init', $feature );

		$this->assertFalse( $_SERVER['feature_booted'] );

		$delayed->boot();

		$this->assertFalse( $_SERVER['feature_booted'], 'Feature should not boot immediately' );

		do_action( 'init' );

		$this->assertTrue( $_SERVER['feature_booted'], 'Feature should boot after hook fires' );
	}

	public function test_delayed_feature_boots_with_array_of_features(): void {
		$feature1 = new class implements Feature {
			public function boot(): void {
				$_SERVER['feature1_booted'] = true;
			}
		};

		$feature2 = new class implements Feature {
			public function boot(): void {
				$_SERVER['feature2_booted'] = true;
			}
		};

		$_SERVER['feature1_booted'] = false;
		$_SERVER['feature2_booted'] = false;

		$delayed = new Delayed_Feature( 'init', [ $feature1, $feature2 ] );

		$delayed->boot();

		$this->assertFalse( $_SERVER['feature1_booted'] );
		$this->assertFalse( $_SERVER['feature2_booted'] );

		do_action( 'init' );

		$this->assertTrue( $_SERVER['feature1_booted'] );
		$this->assertTrue( $_SERVER['feature2_booted'] );
	}

	public function test_delayed_feature_boots_with_callable(): void {
		$delayed = new Delayed_Feature(
			'init',
			function() {
				$_SERVER['feature_booted'] = true;
			}
		);

		$this->assertFalse( $_SERVER['feature_booted'] );

		$delayed->boot();

		$this->assertFalse( $_SERVER['feature_booted'] );

		do_action( 'init' );

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_delayed_feature_respects_hook_priority(): void {
		$_SERVER['execution_order'] = [];

		$delayed_high = new Delayed_Feature(
			'init',
			function() {
				$_SERVER['execution_order'][] = 'high';
			},
			5
		);

		$delayed_low = new Delayed_Feature(
			'init',
			function() {
				$_SERVER['execution_order'][] = 'low';
			},
			15
		);

		$delayed_low->boot();
		$delayed_high->boot();

		do_action( 'init' );

		$this->assertSame( [ 'high', 'low' ], $_SERVER['execution_order'] );
	}

	public function test_delayed_feature_with_default_priority(): void {
		$_SERVER['execution_order'] = [];

		$delayed = new Delayed_Feature(
			'init',
			function() {
				$_SERVER['execution_order'][] = 'default';
			}
		);

		add_action( 'init', function() {
			$_SERVER['execution_order'][] = 'manual';
		}, 10 );

		$delayed->boot();

		do_action( 'init' );

		$this->assertContains( 'default', $_SERVER['execution_order'] );
		$this->assertContains( 'manual', $_SERVER['execution_order'] );
	}

	public function test_delayed_feature_on_custom_hook(): void {
		$feature = new class implements Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$delayed = new Delayed_Feature( 'custom_hook', $feature );

		$delayed->boot();

		$this->assertFalse( $_SERVER['feature_booted'] );

		do_action( 'custom_hook' );

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_delayed_feature_hook_fires_multiple_times(): void {
		$_SERVER['boot_count'] = 0;

		$delayed = new Delayed_Feature(
			'example_hook',
			function() {
				$_SERVER['boot_count']++;
			}
		);

		$delayed->boot();

		$this->assertSame( 0, $_SERVER['boot_count'] );

		do_action( 'example_hook' );
		$this->assertSame( 1, $_SERVER['boot_count'] );

		do_action( 'example_hook' );
		$this->assertSame( 2, $_SERVER['boot_count'] );
	}

	public function test_delayed_feature_with_hookable_feature(): void {
		$feature = new class extends \Mantle\Types\Hookable_Feature {
			#[\Mantle\Support\Attributes\Action( 'example_action' )]
			public function example_action( mixed $args ): void {
				$_SERVER['hook_fired'] = $args;
			}
		};

		$delayed = new Delayed_Feature( 'init', $feature );

		$delayed->boot();

		do_action( 'example_action', 'before_init' );
		$this->assertFalse( $_SERVER['hook_fired'], 'Hook should not fire before init' );

		do_action( 'init' );

		do_action( 'example_action', 'after_init' );
		$this->assertSame( 'after_init', $_SERVER['hook_fired'] );
	}

	public function test_delayed_feature_inherits_validator_group_behavior(): void {
		// Since Delayed_Feature extends Validator_Group, it should inherit
		// validator behavior. This is tested extensively in ValidatorGroupTest.
		// Here we just verify that the delayed hook mechanism works with it.
		$feature = new class implements Feature {
			public function boot(): void {
				$_SERVER['nested_feature_booted'] = true;
			}
		};

		$_SERVER['nested_feature_booted'] = false;

		$delayed = new Delayed_Feature( 'init', $feature );

		$delayed->boot();

		$this->assertFalse( $_SERVER['nested_feature_booted'] );

		do_action( 'init' );

		$this->assertTrue( $_SERVER['nested_feature_booted'] );
	}
}
