<?php
/**
 * Constants for PHPStan
 */

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WPINC', 'wp-includes' );
define( 'DIR_TESTDATA', '/tmp/wordpress-tests-lib/data' );

/**
 * Mirrors tests/Testkit/fixtures/global-functions.php.
 *
 * @param bool $increment Whether the counter should increment.
 */
function mantle_after_wordpress_install( bool $increment = true ): int {
	return 0;
}
