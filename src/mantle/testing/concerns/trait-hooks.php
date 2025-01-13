<?php
/**
 * This file contains the Hooks Trait
 *
 * @package Mantle
 */

// phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.SelfOutsideClass

namespace Mantle\Testing\Concerns;

/**
 * Trait for backing up and restoring hooks during tests.
 */
trait Hooks {
	/**
	 * Saved hooks.
	 *
	 * @var array
	 */
	protected static $hooks_saved = [];

	/**
	 * Saves the action and filter-related globals so they can be restored later.
	 *
	 * Stores $wp_actions, $wp_current_filter, and $wp_filter on a class variable
	 * so they can be restored on tearDown() using _restore_hooks().
	 *
	 * @global array $wp_actions
	 * @global array $wp_current_filter
	 * @global array $wp_filter
	 */
	protected static function backup_hooks() {
		foreach ( [ 'wp_actions', 'wp_current_filter' ] as $global ) {
			self::$hooks_saved[ $global ] = $GLOBALS[ $global ];
		}
		self::$hooks_saved['wp_filter'] = [];
		foreach ( $GLOBALS['wp_filter'] as $hook_name => $hook_object ) {
			self::$hooks_saved['wp_filter'][ $hook_name ] = clone $hook_object;
		}
	}

	/**
	 * Restores the hook-related globals to their state at setUp()
	 * so that future tests aren't affected by hooks set during this last test.
	 *
	 * @global array $wp_actions
	 * @global array $wp_current_filter
	 * @global array $wp_filter
	 */
	protected static function restore_hooks() {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride,WordPress.NamingConventions.PrefixAllGlobals
		foreach ( [ 'wp_actions', 'wp_current_filter' ] as $global ) {
			if ( isset( self::$hooks_saved[ $global ] ) ) {
				$GLOBALS[ $global ] = self::$hooks_saved[ $global ];
			}
		}
		if ( isset( self::$hooks_saved['wp_filter'] ) ) {
			$GLOBALS['wp_filter'] = [];
			foreach ( self::$hooks_saved['wp_filter'] as $hook_name => $hook_object ) {
				$GLOBALS['wp_filter'][ $hook_name ] = clone $hook_object;
			}
		}
		// phpcs:enable
	}
}
