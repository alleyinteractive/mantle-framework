<?php
/**
 * Framework Tests Bootstrap
 *
 * @package Mantle
 */

namespace Mantle\Tests;

use function Mantle\generate_wp_autoloader;

defined( 'MULTISITE' ) || define( 'MULTISITE', true );
define( 'MANTLE_PHPUNIT_INCLUDES_PATH', __DIR__ . '/includes' );
define( 'MANTLE_PHPUNIT_TEMPLATE_PATH', __DIR__ . '/template-parts' );

// Add an autoloader for the fixtures in the '/tests/' folder.
spl_autoload_register( generate_wp_autoloader(
	__NAMESPACE__,
	__DIR__,
) );

\Mantle\Testing\install();
