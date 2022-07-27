<?php
namespace Mantle\Tests\Testkit;

use Mantle\Testing\Installation_Manager;
use Mantle\Testkit\Concerns\Installs_WordPress;
use Mantle\Testkit\Test_Case;

Installation_Manager::instance()->after( function() {
	$_SERVER['__mantle_after_wordpress_install'] ??= 0;

	$_SERVER['__mantle_after_wordpress_install']++;
} );

class Test_Testkit_Install_WordPress extends Test_Case {
	use Installs_WordPress;

	public function test_mantle_is_installed_from_trait() {
		$this->assertEquals( 1, $_SERVER['__mantle_after_wordpress_install'] ?? 0 );
	}
}
