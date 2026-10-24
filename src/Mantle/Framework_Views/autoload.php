<?php
/**
 * Framework Views.
 *
 * @package Mantle
 */

namespace Mantle\Framework_Views;

if ( ! function_exists( __NAMESPACE__ . '\\get_framework_views_path' ) ) {
	/**
	 * Path to the framework views directory.
	 */
	function get_framework_views_path(): string {
		return __DIR__ . '/views/';
	}
}
