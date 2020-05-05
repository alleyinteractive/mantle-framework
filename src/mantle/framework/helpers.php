<?php
/**
 * Mantle Framework Helpers
 *
 * @package Mantle
 */

use Mantle\Framework\Application;

/**
 * Get the available container instance.
 *
 * @param string|null $abstract Component name.
 * @param array       $parameters Parameters to pass to the class.
 * @return mixed|Mantle\Framework\Application
 */
function mantle_app( string $abstract = null, array $parameters = [] ) {
	if ( empty( $abstract ) ) {
		return Application::getInstance();
	}

	return Application::getInstance()->make( $abstract, $parameters );
}
