<?php
namespace Mantle\Tests\Testing;

use Mantle\Testing\Framework_Test_Case;

class Test_Core_Test_Shim extends Framework_Test_Case {
	protected $setup_called = false;

	public function set_up() {
		parent::set_up();

		$this->setup_called = true;
	}

	public function test_go_to() {
		$this->go_to( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}

	public function test_core_set_up_called() {
		$this->assertTrue( $this->setup_called );
	}
}
