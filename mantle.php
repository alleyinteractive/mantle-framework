<?php
/**
 * Plugin Name: Mantle
 * Plugin URI:  https://github.com/alleyinteractive/mantle
 * Description: A framework for powerful WordPress development
 * Author:      Alley
 * Author URI:  https://alley.co/
 * Text Domain: mantle
 * Domain Path: /languages
 * Version:     0.1
 *
 * @package Mantle
 */

namespace Mantle;

define( 'MANTLE_BASE_DIR', __DIR__ );

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/src/mantle/framework/helpers.php';

// Generate the autoloader.
try {
	spl_autoload_register(
		generate_wp_autoloader( __NAMESPACE__, __DIR__ . '/src/mantle' )
	);
} catch ( \Exception $e ) {
	\wp_die( $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Setup the Application
 */
$mantle_app = require_once __DIR__ . '/src/app.php';

// Boot up the Console Kernel if wp-cli.
if ( defined( 'WP_CLI' ) && \WP_CLI ) {
	$mantle_app
		->make( Framework\Console\Kernel::class )
		->handle();
}
