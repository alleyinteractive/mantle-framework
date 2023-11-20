<?php
/**
 * Framework Tests Bootstrap
 *
 * @package Mantle
 */

namespace Mantle\Tests;

define( 'MANTLE_PHPUNIT_INCLUDES_PATH', __DIR__ . '/includes' );
define( 'MANTLE_PHPUNIT_FIXTURES_PATH', __DIR__ . '/fixtures' );
define( 'MANTLE_PHPUNIT_TEMPLATE_PATH', __DIR__ . '/template-parts' );

// Enable debugging flag for local development on the testing framework.
// define( 'MANTLE_TESTING_DEBUG', true );

\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->with_vip_mu_plugins()
	->install_plugin( 'logger', 'https://github.com/alleyinteractive/logger/archive/refs/heads/develop.zip' )
	->install_plugin( 'jetpack', '12.4' )
	->install();
