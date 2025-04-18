<?php
/**
 * Blade_Engine class file.
 *
 * @package Mantle
 */

namespace Mantle\View\Engines;

use Illuminate\View\Compilers\BladeCompiler;
use Mantle\Contracts\View\Engine;
use Mantle\Filesystem\Filesystem;
use Mantle\Http\View\View_Exception;
use Throwable;

use function Mantle\Support\Helpers\validate_file;

/**
 * Blade Template Engine
 */
class Blade_Engine extends Php_Engine {
	/**
	 * A stack of the last compiled templates.
	 *
	 * @var string[]
	 */
	protected array $last_compiled = [];

	/**
	 * The view paths that were compiled or are not expired, keyed by the path.
	 *
	 * @var array<string, true>
	 */
	protected array $compiled_or_not_expired = [];

	/**
	 * Constructor.
	 *
	 * @param Filesystem    $filesystem
	 * @param BladeCompiler $compiler
	 * @param bool         $should_write_files
	 */
	public function __construct( Filesystem $filesystem, protected readonly BladeCompiler $compiler, protected readonly bool $should_write_files ) {
		parent::__construct( $filesystem );
	}

	/**
	 * Evaluate the contents of a view at a given path.
	 *
	 * @param string               $path View path.
	 * @param array<string, mixed> $data View data.
	 */
	public function get( string $path, array $data = [] ): string {
		$this->last_compiled[] = $path;

		// If we aren't able to write the compiled files, we will just return the
		if ( ! $this->should_write_files ) {
			dd('on the fly');
		}

		// If this given view has expired, which means it has simply been edited since
		// it was last compiled, we will re-compile the views so we can evaluate a
		// fresh copy of the view. We'll pass the compiler the path of the view.
		if ( ! isset( $this->compiled_or_not_expired[ $path ] ) && $this->compiler->isExpired( $path ) ) {
			$this->compiler->compile( $path );
		}

		try {
			$results = $this->evaluate_path( $this->compiler->getCompiledPath( $path ), $data );
		} catch ( \Illuminate\View\ViewException $e ) {
			if ( str_contains( $e->getMessage(), 'File does not exist at path' ) ) {
				throw new View_Exception(
					"Unable to compile view [{$path}]. Ensure that the compiled view path (storage/framework/views) is properly created and chmod with 0777.",
					$path,
				);
			}

			throw $e;
		}

		$this->compiled_or_not_expired[ $path ] = true;

		array_pop( $this->last_compiled );

		return $results;
	}

	/**
	 * Handle a view exception.
	 *
	 * @param  \Throwable $e Exception thrown.
	 * @param  int        $ob_level Output buffer level.
	 * @return void
	 *
	 * @throws \Throwable Rethrows the exception thrown.
	 */
	protected function handle_view_exception( Throwable $e, $ob_level ) {
		while ( ob_get_level() > $ob_level ) {
			ob_end_clean();
		}

		throw $e;
	}
}
