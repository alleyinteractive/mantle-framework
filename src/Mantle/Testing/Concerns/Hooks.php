<?php
/**
 * This file contains the Hooks Trait
 *
 * @package Mantle
 */

// phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.SelfOutsideClass

namespace Mantle\Testing\Concerns;

use Mantle\Queue\Database_Scheduler;

/**
 * Hooks back up and restore routines.
 *
 * This trait will back up and restore the WordPress hooks before and after each
 * test. This is useful to ensure that tests do not interfere with each other by
 * adding or removing hooks that could affect the outcome of other tests. Each
 * test should have the hooks restored to the original state before each run.
 *
 * There may be some cases here hooks are duplicated when the application is
 * recreated. This is something we can improve on in the future.
 */
trait Hooks {

	/**
	 * Saved hooks.
	 *
	 * @var array<mixed>
	 */
	protected static $hooks_saved = [];

	/**
	 * Routines to run during setUp().
	 */
	public function hooks_set_up(): void {
		if ( ! self::$hooks_saved ) {
			self::backup_hooks();
		}
	}

	/**
	 * Routines to run during tearDown().
	 */
	public function hooks_tear_down(): void {
		self::restore_hooks();
	}

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
	protected static function backup_hooks(): void {
		foreach ( [ 'wp_actions', 'wp_current_filter' ] as $global ) {
			self::$hooks_saved[ $global ] = $GLOBALS[ $global ];
		}

		self::$hooks_saved['wp_filter'] = [];
		foreach ( $GLOBALS['wp_filter'] as $hook_name => $hook_object ) {
			// Prevent the Mantle queue callback from being saved.
			if ( Database_Scheduler::EVENT === $hook_name ) {
				continue;
			}

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
	protected static function restore_hooks(): void {
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
