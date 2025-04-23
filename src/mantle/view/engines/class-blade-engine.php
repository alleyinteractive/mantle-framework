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
	 * @param bool          $should_write_files Whether to write compiled files or not. This can be set to true if the compiled views directory does not exist or is not writeable.
	 */
	public function __construct( Filesystem $filesystem, protected readonly BladeCompiler $compiler, protected readonly bool $should_write_files ) {
		parent::__construct( $filesystem );
	}

	/**
	 * Retrieve the Blade compiler instance.
	 */
	public function get_compiler(): BladeCompiler {
		return $this->compiler;
	}

	/**
	 * Evaluate the contents of a view at a given path.
	 *
	 * @throws View_Exception Thrown on error writing compiled view.
	 * @throws \Illuminate\View\ViewException Thrown on internal view error.
	 *
	 * @param string               $path View path.
	 * @param array<string, mixed> $data View data.
	 */
	public function get( string $path, array $data = [] ): string {
		$this->last_compiled[] = $path;

		// If we aren't able to write the compiled files, we need to render the
		// blade template dynamically. This could mean that the
		// storage/framework/views directory is not writable or that the user has
		// disabled writing files altogether. Either way, the view cannot be
		// required.
		if ( ! $this->should_write_files ) {
			return $this->render_string( $this->filesystem->get( $path ), $data );
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

	/**
	 * Render a compiled Blade template dynamically.
	 *
	 * This does require eval() so templates must be trusted and sanitized before
	 * being passed to this method.
	 *
	 * Templates are normally written to the filesystem and then require'd to be
	 * rendered. The same security concerns apply to that approach as well. The
	 * difference here is that templates are not written to the filesystem and are
	 * instead evaluated directly.
	 *
	 * @param string               $template The uncompiled Blade template.
	 * @param array<string, mixed> $data The data to pass to the view.
	 */
	public function render_string( string $template, array $data ): string {
		$template = $this->compiler->compileString( $template );

		$ob_level = ob_get_level();

		ob_start();

		try {
			$__view = $template;
			$__data = $data;

			( static function () use ( $__view, $__data ): void {
				global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

				extract( $__data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract, WordPress.PHP.DiscouragedPHPFunctions.extract_extract, Squiz.PHP.Eval.Discouraged

				eval( '?>' . $__view ); // phpcs:ignore WordPress.PHP.Eval.EvalFound, Squiz.PHP.Eval.Discouraged
			} )();
		} catch ( Throwable $e ) {
			$this->handle_view_exception( $e, $ob_level );
		}

		return ltrim( ob_get_clean() );
	}
}
