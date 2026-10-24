<?php
namespace Mantle\Tests;

use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testing\FrameworkTestCase;

class WithFakerTest extends FrameworkTestCase {
	use With_Faker;

	public function test_creates_faker(): void {
		$this->assertInstanceOf( \Faker\Generator::class, $this->opaque( $this->faker ) );
		$this->assertNotEmpty( $this->faker->name() );
	}

	/**
	 * Pass a value through untyped so PHPStan cannot narrow it.
	 */
	private function opaque( mixed $value ): mixed {
		return $value;
	}
}
