<?php
namespace Mantle\Tests\Testkit;

use Faker\Generator;
use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testkit\TestCase;

class WithFakerTest extends TestCase {
	use With_Faker;

	public function test_creates_faker(): void {
		$faker = $this->opaque( $this->faker );

		$this->assertInstanceOf( Generator::class, $faker );
	}

	/**
	 * Return the value unchanged, typed as mixed so PHPStan cannot narrow it.
	 */
	private function opaque( mixed $value ): mixed {
		return $value;
	}
}
