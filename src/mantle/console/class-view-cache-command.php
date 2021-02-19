<?php
/**
 * Package_Discover_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Http\View\View_Finder;
use Mantle\Support\Collection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function Mantle\Framework\Helpers\collect;

/**
 * Package Discover Command
 */
class View_Cache_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'view:cache';

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Compile all Blade templates in an application.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Compile all Blade templates in an application';

	/**
	 * View finder instance.
	 *
	 * @var View_Finder
	 */
	protected $finder;

	/**
	 * Blade compiler.
	 *
	 * @var \Illuminate\View\Compilers\BladeCompiler
	 */
	protected $blade;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 * @param View_Finder $finder Finder instance.
	 */
	public function __construct( Application $app, View_Finder $finder ) {
		if ( ! isset( $app['view.engine.resolver'] ) ) {
			$this->error( 'Missing view engine resolver from the view service provider.', true );
		}

		$this->blade  = $app['view.engine.resolver']->resolve( 'blade' )->getCompiler();
		$this->finder = $finder;
	}

	/**
	 * Compile all blade views.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$this->call( 'mantle view:clear' );

		$paths = $this->finder->get_paths();

		if ( empty( $paths ) ) {
			$this->error( 'No view paths found.', true );
		}

		$this->compile_views( $this->blade_files_in( $paths ) );

		$this->log( 'Blade templates cached successfully.' );
	}

	/**
	 * Compile all views from a collection.
	 *
	 * @param Collection $views Collection of view paths.
	 * @return void
	 */
	protected function compile_views( Collection $views ): void {
		$views->map(
			function ( SplFileInfo $file ) {
				$this->blade->compile( $file->getRealPath() );
			}
		);
	}

	/**
	 * Locate all blade files in a path.
	 *
	 * @param string[] $paths File path.
	 * @return Collection
	 */
	protected function blade_files_in( array $paths ): Collection {
		return collect(
			Finder::create()
				->in( $paths )
				->exclude( 'vendor' )
				->name( '*.blade.php' )
				->files()
		);
	}
}
