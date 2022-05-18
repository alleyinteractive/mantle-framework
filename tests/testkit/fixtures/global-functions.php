<?php

/**
 * This function intentionally defined in the global scope
 * to be used by \Mantle\Testkit\Concerns\Installs_WordPress
 * as a callback.
 *
 * @return int
 */
function mantle_after_wordpress_install(): int {
	static $called = 0;

	$called++;
	return $called;
}
