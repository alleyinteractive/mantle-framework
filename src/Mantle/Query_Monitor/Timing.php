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
	 */
	public static function start( string $name ): void {
		do_action( 'qm/start', $name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Stop a timer.
	 *
	 * @param string $name Time name.
	 */
	public static function stop( string $name ): void {
		do_action( 'qm/stop', $name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Lap a timer
	 *
	 * @param string $name Time name.
	 */
	public static function lap( string $name ): void {
		do_action( 'qm/lap', $name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}
}
