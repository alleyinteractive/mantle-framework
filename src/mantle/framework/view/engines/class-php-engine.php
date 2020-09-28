<?php
namespace Mantle\Framework\View\Engines;

use Mantle\Framework\Contracts\Http\View\View_Loader;
use Mantle\Framework\Contracts\View\Engine;
use Throwable;

/**
 * PHP Template to load WordPress template files.
 */
class Php_Engine implements Engine {
	/**
	 * View Loader
	 *
	 * @var View_Loader
	 */
	// protected $loader;

	// /**
	// * Constructor.
	// *
	// * @param View_Loader $loader
	// */
	// public function __construct( View_Loader $loader ) {
	// $this->loader = $loader;
	// }

	/**
	 * Evaluate the contents of a view at a given path.
	 *
	 * @param string $path View path.
	 * @param array  $data View data.
	 * @return string
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

		// if ( 0 === validate_file( $this->slug ) && 0 === validate_file( $this->slug ) ) {
		// $this->factory->get_container()['view.loader']->load( $this->slug, $this->name );
		// }
	}

	/**
	 * Handle a view exception.
	 *
	 * @param  \Throwable $e
	 * @param  int        $ob_level
	 * @return void
	 *
	 * @throws \Throwable
	 */
	protected function handle_view_exception( Throwable $e, $ob_level ) {
		while ( ob_get_level() > $ob_level ) {
			ob_end_clean();
		}

		throw $e;
	}
}
