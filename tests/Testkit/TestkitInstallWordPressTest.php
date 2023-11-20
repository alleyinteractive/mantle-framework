<?php
namespace Mantle\Tests\Testkit;

use Mantle\Testing\Installation_Manager;
use Mantle\Testkit\Concerns\Installs_WordPress;
use Mantle\Testkit\Test_Case;

// Required for callback.
require_once __DIR__ . '/fixtures/global-functions.php';

Installation_Manager::instance()->after( function() {
	$_SERVER['__mantle_after_wordpress_install'] ??= 0;

	$_SERVER['__mantle_after_wordpress_install']++;
} );

class TestkitInstallWordPressTest extends Test_Case {
	use Installs_WordPress;

	public function test_mantle_is_installed_from_trait() {
		$called = \mantle_after_wordpress_install( false );

		$this->assertEquals( 1, $called );

		$this->assertEquals( 1, $_SERVER['__mantle_after_wordpress_install'] ?? 0 );
	}
}
