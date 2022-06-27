<?php
namespace Mantle\Tests\Testkit;

use Faker\Generator;
use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testkit\Test_Case;

class Test_With_Faker extends Test_Case {
	use With_Faker;

	public function test_creates_faker() {
		$this->assertInstanceOf( Generator::class, $this->faker );
	}
}
