<?php
namespace Mantle\Testing\Concerns;

use Mantle\Console\Application as Console;
use Mantle\Console\Command;
use Mantle\Testing\Framework_Test_Case;

class Test_Interacts_With_Console extends Framework_Test_Case {
	public function test_list_command() {
		$this->command( 'wp mantle list' )
			->assertOutputContains( 'Available commands' )
			->assertOutputNotContains( 'Available nonsense' )
			->assertOk();
	}

	// public function test_closure_command() {
	// 	Console::command( fn () => 'Hello World' )
	// 		->describe( 'Test Closure Command' );
	// }

	// todo: add support for testing a WP-CLI command.
	// public function test_wp_cli_command() {}
}
