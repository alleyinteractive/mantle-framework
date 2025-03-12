<?php
namespace Mantle\Tests\Concerns;

use Mantle\Console\Command;
use Mantle\Facade\Console;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
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

	public function test_command_failure(): void {
		Console::command( 'fail', fn () => $this->fail() );

		$this->command( 'wp mantle fail' )
			->assertOutputContains( 'Command manually failed' )
			->assertFailed();

		Console::command( 'fail:message', fn () => $this->fail( 'With message' ) );

		$this->command( 'wp mantle fail:message' )
			->assertOutputContains( 'With message' )
			->assertFailed();
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

	public function test_class_command(): void {
		$command = new class() extends Command {
			protected $signature = 'test:class-command';

			public function __invoke() {
				$this->info( 'Hello World' );
			}
		};

		Console::register( $command::class );

		$this->command( 'wp mantle test:class-command' )
			->assertOutputContains( 'Hello World' )
			->assertOk();
	}

	public function test_wp_cli_command() {
		$this->markTestSkipped( 'WP-CLI commands are not supported yet.' );
	}
}
