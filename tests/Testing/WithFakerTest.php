<?php
namespace Mantle\Tests;

use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testing\FrameworkTestCase;

class WithFakerTest extends FrameworkTestCase {
	use With_Faker;

	public function test_creates_faker(): void {
		$this->assertNotEmpty( $this->faker->name() );
	}
}
