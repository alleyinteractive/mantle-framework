<?php
namespace Mantle\Tests\Types;

use Alley\WP\Types\Feature;
use Mantle\Types\Validator_Group;
use Mantle\Testing\FrameworkTestCase;

class RequestTest extends FrameworkTestCase {
	public function test_boots_when_request_matches(): void {
		$_SERVER['REQUEST_URI']    = '/example/test';
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$feature = new #[\Mantle\Types\Attributes\Request( '/example/*' )] class implements Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		( new Validator_Group( $feature ) )->boot();

		$this->assertTrue( $_SERVER['feature_booted'] );
	}

	public function test_does_not_boot_when_method_does_not_match(): void {
		$_SERVER['REQUEST_URI']    = '/example/test';
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$feature = new #[\Mantle\Types\Attributes\Request( '/example/*', 'GET' )] class implements Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		( new Validator_Group( $feature ) )->boot();

		$this->assertFalse( $_SERVER['feature_booted'] );
	}

	public function test_does_not_boot_with_invalid_request(): void {
		$_SERVER['REQUEST_URI']    = '/another/path';
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$feature = new #[\Mantle\Types\Attributes\Request( '/example/*', 'GET' )] class implements Feature {
			public function boot(): void {
				$_SERVER['feature_booted'] = true;
			}
		};

		$_SERVER['feature_booted'] = false;

		( new Validator_Group( $feature ) )->boot();

		$this->assertFalse( $_SERVER['feature_booted'] );
	}
}
