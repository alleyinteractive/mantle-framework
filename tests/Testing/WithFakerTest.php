<?php
namespace Mantle\Tests;

use Faker\Generator;
use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testing\FrameworkTestCase;

class WithFakerTest extends FrameworkTestCase {
	use With_Faker;

	public function test_creates_faker() {
		$this->assertInstanceOf( Generator::class, $this->faker );
	}
}
