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

if ( ! function_exists( 'ensure_trailingslash' ) ) :
	/**
	 * Appends a trailing slash.
	 *
	 * @param string $string String to append a trailing slash to.
	 * @return string
	 */
	function ensure_trailingslash( $string ) {
		return remove_trailingslash( $string ) . '/';
	}
endif;

if ( ! function_exists( 'remove_trailingslash' ) ) :
	/**
	 * Removes trailing forward slashes and backslashes if they exist.
	 *
	 * @param string $string What to remove the trailing slashes from.
	 * @return string String without the trailing slashes.
	 */
	function remove_trailingslash( $string ) {
		return rtrim( $string, '/\\' );
	}
endif;
