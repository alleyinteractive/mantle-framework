<?php
namespace Mantle\Testing;

class Test_Core_Test_Shim extends Framework_Test_Case {
	public function test_go_to() {
		$this->go_to( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}
}
