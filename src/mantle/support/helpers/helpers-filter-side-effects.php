<?php
/**
 * Filter side-effect functions
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

/**
 * All-in-one wrapper for `add_filter()` and `generate_filter_side_effect()`.
 *
 * @param string   $tag             Filter name.
 * @param callable $function_to_add Callback.
 * @param int      $priority        Priority.
 * @param int      $accepted_args   Accepted arguments.
 * @return true|void
 */
function add_filter_side_effect( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	return \add_filter( $tag, generate_filter_side_effect( $function_to_add ), $priority, $accepted_args );
}

/**
 * Returns a function that can be added to a filter to use the filter like an action.
 *
 * @param callable $callback Callback function.
 * @return callable Function that returns the filtered value after calling the callable.
 */
function generate_filter_side_effect( $callback ) {
	return function ( ...$args ) use ( $callback ) {
		$callback( ...$args );
		return $args[0];
	};
}
