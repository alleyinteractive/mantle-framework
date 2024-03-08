<?php
/**
 * Php_Engine class file.
 *
 * @package Mantle
 */

namespace Mantle\View\Engines;

use Mantle\Contracts\View\Engine;
use Throwable;

use function Mantle\Support\Helpers\validate_file;

/**
 * PHP Template to load WordPress template files.
 */
class Php_Engine implements Engine {
	/**
	 * Evaluate the contents of a view at a given path.
	 *
	 * @param string $path View path.
	 * @param array  $data View data.
	 */
	public function get( string $path, array $data = [] ): string {
		$ob_level = ob_get_level();

		ob_start();

		try {
			if ( 0 === validate_file( $path ) && 0 === validate_file( $path ) ) {
				load_template( $path, false );
			}
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
