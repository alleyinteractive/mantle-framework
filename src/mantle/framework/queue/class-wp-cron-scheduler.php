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

		app( 'queue.worker' )->run(
			(int) config( 'queue.batch_size', 1 ),
			$queue
		);
	}

	/**
	 * Schedule the next run of the cron for a queue.
	 *
	 * @param string $queue Queue name.
	 * @param int    $delay Delay in seconds, defaults to none.
	 * @return bool
	 */
	public static function schedule( string $queue = null, int $delay = 0 ): bool {
		if ( ! $queue ) {
			$queue = 'default';
		}

		if ( ! \wp_next_scheduled( static::EVENT, [ $queue ] ) ) {
			\wp_schedule_single_event( time() + $delay, static::EVENT, [ $queue ] );
		}

		return true;
	}

	/**
	 * Schedule the next run of a queue.
	 *
	 * Checks if there are items remaining in the queue before running. Uses the
	 * application's configuration if specified, otherwise defaults to now.
	 *
	 * @param string $queue Queue name.
	 * @return bool Flag if the next run was scheduled.
	 */
	public static function schedule_next_run( string $queue = null ): bool {
		$has_remaining = \get_posts(
			[
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'post_type'           => Wp_Cron_Provider::OBJECT_NAME,
				'posts_per_page'      => 1,
				'suppress_filters'    => false,
				'post_status'         => 'publish',
				'tax_query'           => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => Wp_Cron_Provider::OBJECT_NAME,
						'terms'    => Wp_Cron_Provider::get_queue_term_id( $queue ),
					],
				],
			]
		);

		if ( empty( $has_remaining ) ) {
			return false;
		}

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
