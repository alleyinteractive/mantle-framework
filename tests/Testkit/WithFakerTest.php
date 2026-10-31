<?php
namespace Mantle\Tests\Testkit;

use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testkit\TestCase;

class WithFakerTest extends TestCase {
	use With_Faker;

	public function test_creates_faker(): void {
		// The typed `Generator` property guarantees the instance; this fails if it is uninitialized.
		$this->expectNotToPerformAssertions();

		$this->faker->word();
	}
}
