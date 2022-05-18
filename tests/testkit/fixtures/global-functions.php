<?php

/**
 * This function intentionally defined in the global scope
 * to be used by \Mantle\Testkit\Concerns\Installs_WordPress
 * as a callback.
 *
 * @param bool $increment Defines if the counter should increment or not. Defaults to true.
 * @return int
 */
function mantle_after_wordpress_install( bool $increment = true ): int {
	static $called = 0;

	if( true === $increment ) {
		$called++;
	}

	return $called;
}
