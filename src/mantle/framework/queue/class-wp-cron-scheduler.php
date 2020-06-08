<?php
/**
 * Wp_Cron_Scheduler class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue;

/**
 * WordPress Cron Scheduler
 */
class Wp_Cron_Scheduler {
	/**
	 * Cron event.
	 *
	 * @var string
	 */
	public const EVENT = 'mantle_queue';

	/**
	 * Register the cron scheduler.
	 */
	public static function register() {
		\add_action( Wp_Cron_Scheduler::EVENT, [ static::class, 'on_queue_run' ] );
	}

	/**
	 * Callback for the cron event.
	 *
	 * @todo Abstract this a bit, allow configuration to control some of this.
	 *
	 * @param string $queue Queue name.
	 */
	public static function on_queue_run( $queue ) {
		if ( ! $queue ) {
			$queue = 'default';
		}

		mantle_app( 'queue.worker' )->run_batch( (int) mantle_config( 'queue.batch_size', 1 ), $queue );
	}

	/**
	 * Schedule the next run of the cron for a queue.
	 *
	 * @param string $queue Queue name.
	 * @param int    $delay Delay in seconds, defaults to none.
	 */
	public static function schedule( string $queue = null, int $delay = 0 ) {
		if ( ! $queue ) {
			$queue = 'default';
		}

		if ( ! \wp_next_scheduled( static::EVENT, [ $queue ] ) ) {
			\wp_schedule_single_event( time() + $delay, static::EVENT, [ $queue ] );
		}
	}

	/**
	 * Schedule the next run of a queue.
	 *
	 * Uses the application's configuration if specified, otherwise defaults to now.
	 *
	 * @param string $queue Queue name.
	 */
	public static function schedule_next_run( string $queue = null ) {
		if ( ! $queue ) {
			$queue = 'default';
		}

		$delay = mantle_config( 'queue.wordpress.delay', [] );

		// Support queue-specific delay.
		if ( is_array( $delay ) ) {
			$delay = $delay[ $queue ] ?? 0;
		}

		return static::schedule( $queue, $delay );
	}
}
