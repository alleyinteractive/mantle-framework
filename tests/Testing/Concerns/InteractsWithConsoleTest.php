<?php
namespace Mantle\Tests\Concerns;

use Mantle\Facade\Console;
use Mantle\Testing\Framework_Test_Case;

/**
 * @group testing
 */
class InteractsWithConsoleTest extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// Manually bind the console kernel if it's not bound already.
		if ( ! isset( $this->app[ \Mantle\Contracts\Console\Kernel::class ] ) ) {
			$this->app[ \Mantle\Contracts\Console\Kernel::class ] = $this->app->make(
				\Mantle\Framework\Console\Kernel::class,
			);
		}
	}

	public function test_list_command() {
		$this->command( 'wp mantle list' )
			->assertOutputContains( 'Available commands' )
			->assertOutputNotContains( 'Available nonsense' )
			->assertOk();
	}

	public function test_closure_command() {
		Console::command( 'hello-world', fn () => $this->info( 'Hello World' ) )
			->describe( 'Command description' );

		$this->command( 'wp mantle hello-world' )
			->assertOutputContains( 'Hello World' )
			->assertOk();

		$this->command( 'wp mantle list' )->assertOutputContains( 'Command description' );
	}

	public function test_closure_command_input() {
		Console::command( 'hello {name}', function ( $name ) {
			$this->info( "Hello {$name}" );
		} );

		$this->command( 'wp mantle hello', [ 'name' => 'john' ] )
			->assertOutputContains( 'Hello john' )
			->assertOk();
	}

	public function test_wp_cli_command() {
		$this->markTestSkipped( 'WP-CLI commands are not supported yet.' );
	}
}
