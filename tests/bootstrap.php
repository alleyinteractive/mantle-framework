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
define( 'MANTLE_TESTING_DEBUG', true );

// Testing an specific version of mantle-ci.
putenv( 'MANTLE_CI_BRANCH=85b3aff18f181fd2c7a08dfd639fa4688fe1179a' );

\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->with_object_cache()
	->with_vip_mu_plugins()
	->install();
