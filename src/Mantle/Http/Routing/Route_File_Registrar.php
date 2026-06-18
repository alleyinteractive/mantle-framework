<?php
/**
 * Route_File_Registrar class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

/**
 * Route File Registrar
 */
class Route_File_Registrar {

	/**
	 * Create a new route file registrar instance.
	 *
	 * @param Router $router Router instance.
	 */
	public function __construct( protected Router $router ) {
	}

	/**
	 * Require the given routes file.
	 *
	 * @param  string $routes Routes to register.
	 */
	public function register( string $routes ): void {
		$router = $this->router;
		require $routes; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
	}
}
