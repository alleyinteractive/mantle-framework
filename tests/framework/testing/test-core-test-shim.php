<?php
namespace Mantle\Framework\Testing;

class Test_Core_Test_Shim extends Test_Case {
	public function test_go_to() {
		$this->go_to( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}
}
