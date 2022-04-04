<?php
namespace Mantle\Tests\Testkit;

use Mantle\Testkit\Test_Case;

class Test_Testkit_Test_Case extends Test_Case {
	public function test_create_application() {
		$this->assertInstanceOf( \Mantle\Contracts\Application::class, $this->create_application() );
	}

	public function test_make_request() {
		$this->get( '/' )->assertOk();
		$this->get( '/unknown/' )->assertNotFound();
	}
}
