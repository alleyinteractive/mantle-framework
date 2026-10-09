<?php
namespace Mantle\Tests\Console;

use Mantle\Facade\Console;
use Mantle\Framework\Console\Hook_Usage_Command;
use Mantle\Testing\FrameworkTestCase;

class HookUsageCommandTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();

		if ( ! isset( $this->app[ \Mantle\Contracts\Console\Kernel::class ] ) ) {
			$this->app[ \Mantle\Contracts\Console\Kernel::class ] = $this->app->make(
				\Mantle\Framework\Console\Kernel::class,
			);
		}

		Console::register( Hook_Usage_Command::class );
	}

	public function test_action_usage(): void {
		$base     = __DIR__ . '/hook-usage/base-example.php';
		$sub      = __DIR__ . '/hook-usage/sub/sub-example.php';
		$hookable = __DIR__ . '/hook-usage/hookable-example.php';
		$edge     = __DIR__ . '/hook-usage/edge-cases.php';

		$this->assertEqualsCanonicalizing(
			[
				[ 'file' => $base, 'line' => 2, 'method' => 'add_action' ],
				[ 'file' => $base, 'line' => 6, 'method' => 'add_action' ],
				[ 'file' => $base, 'line' => 10, 'method' => 'add_action' ],
				[ 'file' => $base, 'line' => 28, 'method' => 'add_action' ],
				[ 'file' => $base, 'line' => 40, 'method' => 'add_action' ],
				[ 'file' => $sub, 'line' => 2, 'method' => 'add_action' ],
				[ 'file' => $sub, 'line' => 6, 'method' => 'add_action' ],
				[ 'file' => $sub, 'line' => 10, 'method' => 'add_action' ],
				[ 'file' => $hookable, 'line' => 11, 'method' => 'on_init' ],
				[ 'file' => $hookable, 'line' => 13, 'method' => 'on_init_at_20' ],
				[ 'file' => $hookable, 'line' => 15, 'method' => 'action__init' ],
				[ 'file' => $hookable, 'line' => 21, 'method' => '#[Action]' ],
				[ 'file' => $hookable, 'line' => 24, 'method' => '#[Action]' ],
				[ 'file' => $edge, 'line' => 14, 'method' => 'add_action' ],
				[ 'file' => $edge, 'line' => 16, 'method' => 'add_action' ],
				[ 'file' => $edge, 'line' => 18, 'method' => 'do_action' ],
				[ 'file' => $edge, 'line' => 22, 'method' => 'remove_action' ],
				[ 'file' => $edge, 'line' => 28, 'method' => 'did_action' ],
			],
			$this->get_usage( 'init' ),
		);
	}

	public function test_filter_usage(): void {
		$base     = __DIR__ . '/hook-usage/base-example.php';
		$sub      = __DIR__ . '/hook-usage/sub/sub-example.php';
		$hookable = __DIR__ . '/hook-usage/hookable-example.php';
		$edge     = __DIR__ . '/hook-usage/edge-cases.php';

		$this->assertEqualsCanonicalizing(
			[
				[ 'file' => $base, 'line' => 19, 'method' => 'add_filter' ],
				[ 'file' => $sub, 'line' => 19, 'method' => 'add_filter' ],
				[ 'file' => $hookable, 'line' => 17, 'method' => 'filter__the_filter' ],
				[ 'file' => $hookable, 'line' => 24, 'method' => '#[Filter]' ],
				[ 'file' => $edge, 'line' => 20, 'method' => 'apply_filters' ],
			],
			$this->get_usage( 'the_filter' ),
		);
	}

	public function test_hook_with_regex_characters(): void {
		$this->assertEquals(
			[
				[ 'file' => __DIR__ . '/hook-usage/edge-cases.php', 'line' => 26, 'method' => 'add_action' ],
			],
			$this->get_usage( 'the/slashed/hook' ),
		);
	}

	public function test_single_file_search_path(): void {
		$this->assertCount(
			3,
			$this->get_usage( 'init', __DIR__ . '/hook-usage/sub/sub-example.php' ),
		);
	}

	public function test_table_output(): void {
		$this->command( 'wp mantle hook-usage', [
			'hook'          => 'the_filter',
			'--search-path' => __DIR__ . '/hook-usage/sub',
		] )
			->assertOutputContains( 'sub-example.php' )
			->assertOutputContains( 'add_filter' )
			->assertOk();
	}

	public function test_no_usage(): void {
		$this->command( 'wp mantle hook-usage', [
			'hook'          => 'unknown_hook',
			'--search-path' => __DIR__ . '/hook-usage',
		] )
			->assertOutputContains( 'No usage found' )
			->assertFailed();
	}

	public function test_invalid_search_path(): void {
		$this->command( 'wp mantle hook-usage', [
			'hook'          => 'init',
			'--search-path' => __DIR__ . '/does-not-exist',
		] )
			->assertOutputContains( 'No valid search paths' )
			->assertFailed();
	}

	/**
	 * @return array<int, array{file: string, line: int, method: string}>
	 */
	protected function get_usage( string $hook, ?string $path = null ): array {
		$output = $this->command( 'wp mantle hook-usage', [
			'hook'          => $hook,
			'--search-path' => $path ?? __DIR__ . '/hook-usage',
			'--format'      => 'json',
		] )
			->assertOk()
			->execute()
			->get_output();

		return json_decode( $output, true );
	}
}
