<?php
/**
 * Autoloader.
 *
 * @package Mantle
 */

namespace Mantle;

/**
 * Generate an autoloader for the WordPress file naming conventions.
 *
 * @deprecated Use https://github.com/alleyinteractive/wordpress-autoloader or
 * https://github.com/alleyinteractive/composer-wordpress-autoloader instead,
 * will be removed shortly.
 *
 * @param string $namespace Namespace to autoload.
 * @param string $root_path Path in which to look for files.
 * @return \Closure Function for spl_autoload_register().
 */
function generate_wp_autoloader( string $namespace, string $root_path ): callable {
	trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		'Mantle\generate_wp_autoloader() is deprecated. Use alleyinteractive/wordpress-autoloader instead.',
		E_USER_DEPRECATED,
	);

	return \Alley_Interactive\Autoloader\Autoloader::generate(
		$namespace,
		$root_path,
	);
}
