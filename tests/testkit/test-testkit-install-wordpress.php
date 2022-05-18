<?php
namespace Mantle\Tests\Testkit;

use Mantle\Testkit\Concerns\Installs_WordPress;
use Mantle\Testkit\Test_Case;

// Required for callback.
require_once __DIR__ . '/fixtures/global-functions.php';

class Test_Testkit_Install_WordPress extends Test_Case {
	use Installs_WordPress;

	public function test_mantle_is_installed_from_trait() {
		$called = \mantle_after_wordpress_install( false );

		$this->assertEquals( 1, $called );
	}

}
