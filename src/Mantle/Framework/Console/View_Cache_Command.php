<?php
/**
 * View_Cache_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Illuminate\View\Compilers\BladeCompiler;
use Mantle\Console\Command;
use Mantle\Contracts\Application;
use Mantle\Filesystem\Filesystem;
use Mantle\Http\View\View_Finder;
use Mantle\Support\Collection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\collect;

/**
 * View Cache Command
 */
class View_Cache_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'view:cache {--wp-content} {--path=} {--skip-clear}';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Compile all Blade templates in an application';

	/**
	 * Blade compiler.
	 */
	protected BladeCompiler $blade;

	/**
	 * View finder.
	 */
	protected View_Finder $finder;

	/**
	 * Compile all blade views.
	 */
	public function handle(): int {
		if ( ! isset( $this->container['view.engine.resolver'] ) ) {
			$this->error( 'Missing view engine resolver from the view service provider.' );

			return Command::FAILURE;
		}

		$this->blade = $this->container['view.engine.resolver']->resolve( 'blade' )->get_compiler();

		if ( ! $this->option( 'skip-clear', false ) ) {
			$this->call( 'mantle view:clear' );
		}

		$compiled_path = $this->container['config']['view.compiled'] ?? null;

		if ( empty( $compiled_path ) ) {
			$this->error( 'No compiled view path found.' );

			return self::FAILURE;
		}

		if ( ! ( new Filesystem() )->ensure_directory_exists( $compiled_path ) ) {
			$this->error( "Unable to create the compiled view directory [{$compiled_path}]" );

			return Command::FAILURE;
		}

		if ( $this->option( 'wp-content', false ) ) {
			return $this->handle_compile_wp_content();
		}

		$paths = $this->mixed_option( 'path' )->string();

		if ( $paths !== '' && $paths !== '0' ) {
			return $this->handle_compile_path( $paths );
		}

		if ( $this->container->is_running_in_console_isolation() ) {
			return $this->handle_console_isolation();
		}

		// Cannot be moved to method type hint because it is not compatible with
		// console isolation mode.
		$this->finder = $this->container->make( View_Finder::class );

		$paths = $this->finder->get_paths();

		if ( empty( $paths ) ) {
			$this->error( 'No view paths found.' );
			return Command::FAILURE;
		}

		$this->compile_views( $this->blade_files_in( $paths )->exclude( 'vendor' ) );

		$this->success( 'Blade templates cached successfully.' );

		return Command::SUCCESS;
	}

	/**
	 * Compile all views from a collection.
	 *
	 * @param Finder $finder Finder instance.
	 */
	protected function compile_views( Finder $finder ): void {
		foreach ( $finder as $file ) {
			$this->info( "Compiling [{$file->getRealPath()}]", 'vvv' );

			$this->blade->compile( $file->getRealPath() );
		}
	}

	/**
	 * Locate all blade files in a path.
	 *
	 * @param string[] $paths File path.
	 */
	protected function blade_files_in( array $paths ): Finder {
		return Finder::create()
			->in( $paths )
			->name( '*.blade.php' )
			->files();
	}

	/**
	 * Handle console isolation mode.
	 */
	protected function handle_console_isolation(): int {
		$base = $this->container->get_base_path();

		$this->info( "Running in console isolation mode. Compiling all Blade templates found in [{$base}]" );

		$this->compile_views( $this->blade_files_in( [ $base ] ) );

		return self::SUCCESS;
	}

	/**
	 * Handle the --wp-content option and compile all views in wp-content.
	 */
	protected function handle_compile_wp_content(): int {
		// Get the path to wp-content from the current directory (which is a child of wp-content).
		$wp_content_dir = (string) preg_replace( '#/wp-content/.*$#', '/wp-content', __DIR__ );

		if ( ! is_dir( $wp_content_dir ) ) {
			$this->error( 'No wp-content directory found.' );

			return Command::FAILURE;
		}

		$this->info( "Compiling all Blade templates found in [{$wp_content_dir}]" );

		$this->compile_views( $this->blade_files_in( [ $wp_content_dir ] ) );

		return Command::SUCCESS;
	}

	/**
	 * Handle compile path.
	 *
	 * @param string $path Path to compile.
	 */
	protected function handle_compile_path( string $path ): int {
		$cwd = getcwd();

		collect( explode( ',', $path ) )
			->map( fn ( $item ) => $cwd . DIRECTORY_SEPARATOR . $item )
			->each( function ( string $path ): void {
				if ( ! is_dir( $path ) ) {
					$this->error( "Path [{$path}] does not exist. Skipping..." );

					return;
				}

				$this->info( "Compiling all Blade templates found in [{$path}]" );

				$this->compile_views( $this->blade_files_in( [ $path ] ) );
			} )
			->all();

		return Command::SUCCESS;
	}
}
