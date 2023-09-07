<?php
/**
 * Scheduler class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

/**
 * WordPress Cron Scheduler
 *
 * @todo Add concurrency support to schedule multiple batches when there is a large backlog.
 */
class Scheduler {
	/**
	 * Cron event.
	 *
	 * @var string
	 */
	public const EVENT = 'mantle_queue';

	/**
	 * Callback for the cron event.
	 *
	 * @param string $queue Queue name, optional.
	 */
	public static function on_queue_run( ?string $queue = null ) {
		if ( ! $queue ) {
			$queue = 'default';
		}

		app( 'queue.worker' )->run(
			static::get_configuration_value( 'batch_size', $queue, 100 ),
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
	 * Unschedule the next run of the cron for a queue.
	 *
	 * @param string $queue Queue name.
	 */
	public static function unschedule( string $queue = null ): void {
		if ( ! $queue ) {
			$queue = 'default';
		}

		\wp_clear_scheduled_hook( static::EVENT, [ $queue ] );
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
	public static function schedule_next_run( ?string $queue = null ): bool {
		if ( ! $queue ) {
			$queue = 'default';
		}

		/** @var \Mantle\Queue\Providers\WordPress\Provider */
		$provider = app( 'queue' )->get_provider( 'wordpress' );

		// Ensure the queue job isn't scheduled if there are no items in the queue.
		if ( ! $provider->pending_count( $queue ) ) {
			static::unschedule( $queue );

			return false;
		}

		return static::schedule( $queue, static::get_configuration_value( 'delay', $queue, 0 ) );
	}

	/**
	 * Retrieve a configuration value for a queue.
	 *
	 * @param string $key Configuration key.
	 * @param string $queue Queue name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected static function get_configuration_value( string $key, string $queue = null, mixed $default = null ): mixed {
		$config = config();

		// Check for a queue-specific configuration value.
		if ( $queue && $config->has( "queue.wordpress.queues.{$queue}.{$key}" ) ) {
			return $config->get( "queue.wordpress.queues.{$queue}.{$key}" );
		}

		// Check for a default configuration for the queue provider.
		if ( $config->has( "queue.wordpress.{$key}" ) ) {
			return $config->get( "queue.wordpress.{$key}" );
		}

		// Check for a default configuration for the queue configuration.
		if ( $config->has( "queue.{$key}" ) ) {
			return $config->get( "queue.{$key}" );
		}

		return $default;
	}
}
