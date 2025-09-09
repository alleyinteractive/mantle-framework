<?php
namespace Mantle\Tests\Types;

use Alley\WP\Types\Feature;
use Mantle\Types\Validator_Group;
use Mantle\Testing\Attributes\Environment;
use Mantle\Testing\FrameworkTestCase;

#[Environment( Environment::STAGING )]
class EnvironmentTest extends FrameworkTestCase {
	public function test_boots_when_environment_matches(): void {
		$this->assertEquals( 'staging', wp_get_environment_type() );

		$feature = new #[\Mantle\Types\Attributes\Environment( Environment::STAGING )] class implements Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		( new Validator_Group( $feature ) )->boot();

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_does_not_boot_with_invalid_environment(): void {
		$this->assertEquals( 'staging', wp_get_environment_type() );

		$feature = new #[\Mantle\Types\Attributes\Environment( Environment::PRODUCTION )] class implements Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		( new Validator_Group( $feature ) )->boot();

		$this->assertFalse( $_SERVER['feature_booted'] );
	}
}
