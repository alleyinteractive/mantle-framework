<?php
/**
 * Framework Tests Bootstrap
 *
 * @package Mantle
 */

namespace Mantle\Tests;

define( 'MANTLE_PHPUNIT_INCLUDES_PATH', __DIR__ . '/includes' );
define( 'MANTLE_PHPUNIT_TEMPLATE_PATH', __DIR__ . '/template-parts' );

\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->install();
