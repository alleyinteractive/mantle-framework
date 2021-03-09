<?php
/**
 * Timing class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor;

/**
 * Query Monitor Timing
 *
 * @link https://querymonitor.com/blog/2018/07/profiling-and-logging/
 */
class Timing {
	/**
	 * Start a timer.
	 *
	 * @param string $name Time name.
	 * @return void
	 */
	public static function start( string $name ) {
		do_action( 'qm/start', $name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Stop a timer.
	 *
	 * @param string $name Time name.
	 * @return void
	 */
	public static function stop( string $name ) {
		do_action( 'qm/stop', $name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Lap a timer
	 *
	 * @param string $name Time name.
	 * @return void
	 */
	public static function lap( string $name ) {
		do_action( 'qm/lap', $name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}
}
