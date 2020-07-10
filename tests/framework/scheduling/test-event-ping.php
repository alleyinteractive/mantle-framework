<?php
namespace Mantle\Tests\Framework\Scheduling;

use Mantle\Framework\Testing\Test_Case;

class Test_Event_Ping extends Test_Case {
	public function test_test() {
		dd($this->app->is_booted());
		$this->assertTrue( true );
	}
}
