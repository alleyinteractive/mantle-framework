<?php
/**
 * Functions from the core unit test library that help with testing.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,
 *
 * @package Mantle
 */

if ( ! function_exists( 'rand_str' ) ) :
	/**
	 * Provide a random string.
	 *
	 * @param int $len String length.
	 * @return string
	 */
	function rand_str( $len = 32 ): string {
		return substr( md5( uniqid( wp_rand() ) ), 0, $len );
	}
endif;
