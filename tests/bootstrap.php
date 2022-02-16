<?php
/**
 * Framework Tests Bootstrap
 *
 * @package Mantle
 */

namespace Mantle\Tests;

require_once __DIR__ . '/../vendor/wordpress-autoload.php';

defined( 'MULTISITE' ) || define( 'MULTISITE', true );
define( 'MANTLE_PHPUNIT_INCLUDES_PATH', __DIR__ . '/includes' );
define( 'MANTLE_PHPUNIT_TEMPLATE_PATH', __DIR__ . '/template-parts' );

\Mantle\Testing\install();
