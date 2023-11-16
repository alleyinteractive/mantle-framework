<?php
namespace Mantle\Tests;

use Faker\Generator;
use Mantle\Testing\Concerns\With_Faker;

class Test_With_Faker extends Framework_Test_Case {
	use With_Faker;

	public function test_creates_faker() {
		$this->assertInstanceOf( Generator::class, $this->faker );
	}
}
