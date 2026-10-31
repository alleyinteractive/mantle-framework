<?php
namespace Mantle\Tests\Support;

use Mantle\Testing\FrameworkTestCase;

class InvadeTest extends FrameworkTestCase {
	public function test_invade_private_method(): void {
		$object = new class() {
			private function secret( string $value ): string { // @phpstan-ignore method.unused (called through invade() below)
				return strtoupper( $value );
			}
		};

		$this->assertSame( 'FOO', invade( $object )->secret( 'foo' ) );
	}

	public function test_invade_private_property(): void {
		$object = new class() {
			private string $secret = 'original';

			public function get_secret(): string {
				return $this->secret;
			}
		};

		$this->assertSame( 'original', invade( $object )->secret );

		invade( $object )->secret = 'changed';

		$this->assertSame( 'changed', $object->get_secret() );
	}
}
