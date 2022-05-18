<?php
/**
 * Installs_WordPress trait file
 *
 * @package Mantle
 */

namespace Mantle\Testkit\Concerns;

/**
 * Concern for handling WordPress installation outside of the bootstrap.
 */
trait Installs_WordPress {
	/**
	 * Manages installing WordPress.
	 *
	 * This is useful for installing WordPress in integration tests, while leaving the bootstrap
	 * free of this overhead during unit tests. To perform actions after WordPress is installed, you
	 * simply need to define a function `mantle_after_wordpress_install` in your bootstrap file.
	 */
	public static function installs_wordpress_set_up_before_class(): void {
		static $installed = false;

		if ( true === $installed ) {
			return;
		}

		$callback = function_exists( 'mantle_after_wordpress_install' ) ? 'mantle_after_wordpress_install' : null;

		\Mantle\Testing\install( $callback );

		$installed = true;
	}
}
