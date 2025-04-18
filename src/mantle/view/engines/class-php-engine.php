<?php
/**
 * Php_Engine class file.
 *
 * @package Mantle
 */

namespace Mantle\View\Engines;

use Mantle\Contracts\View\Engine;
use Mantle\Filesystem\Filesystem;
use Throwable;

/**
 * PHP Template to load WordPress template files.
 */
class Php_Engine implements Engine {
	/**
	 * Constructor.
	 *
	 * @param Filesystem $filesystem
	 */
	public function __construct( protected readonly Filesystem $filesystem ) {}

	/**
	 * Evaluate the contents of a view at a given path.
	 *
	 * @param string               $path View path.
	 * @param array<string, mixed> $data View data.
	 */
	public function get( string $path, array $data = [] ): string {
		return $this->evaluate_path( $path, $data );
	}

	/**
	 * Get the evaluated contents of the view at the given path.
	 *
	 * @param string               $path View path.
	 * @param array<string, mixed> $data View data.
	 */
	protected function evaluate_path( string $path, array $data ): string {
		$ob_level = ob_get_level();

		ob_start();

		try {
			$this->filesystem->get_require( $path, $data );
		} catch ( Throwable $e ) {
			$this->handle_view_exception( $e, $ob_level );
		}

		return ltrim( ob_get_clean() );
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
