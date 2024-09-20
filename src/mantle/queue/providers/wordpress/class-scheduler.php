<?php
/**
 * Scheduler class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

use Mantle\Support\Collection;

use function Mantle\Support\Helpers\collect;

/**
 * WordPress Cron Scheduler
 */
class Scheduler {
	/**
	 * Cron event.
	 *
	 * @var string
	 */
	public const EVENT = 'mantle_queue';

	/**
	 * Pending queues that need scheduling.
	 *
	 * @var string[]
	 */
	public static array $pending_queues = [];

	/**
	 * Handle a job being queued and ensure the cron is scheduled for the queue.
	 *
	 * @param string $queue Queue name.
	 */
	public static function on_job_queued( string $queue ): void {
		if ( ! in_array( $queue, static::$pending_queues, true ) ) {
			static::$pending_queues[] = $queue;
		}

		if ( ! has_action( 'shutdown', [ self::class, 'schedule_on_shutdown' ] ) ) {
			add_action( 'shutdown', [ self::class, 'schedule_on_shutdown' ] );
		}
	}

	/**
	 * Schedule the next run of the queue on shutdown for all pending queues.
	 *
	 * Once a queue job is dispatched, the queue will be scheduled to run on
	 * shutdown to deduplicate any scheduling calls.
	 */
	public static function schedule_on_shutdown(): void {
		foreach ( static::$pending_queues as $pending_queue ) {
			static::schedule_next_run( $pending_queue );
		}

		static::$pending_queues = [];
	}

	/**
	 * Callback for the cron event.
	 *
	 * @param string $queue Queue name, optional.
	 */
	public static function run( ?string $queue = null ): void {
		if ( ! $queue ) {
			$queue = 'default';
		}

		wp_raise_memory_limit( 'cron' );

		app( 'queue.worker' )->run(
			static::get_configuration_value( 'batch_size', $queue, 100 ),
			$queue
		);
	}

	/**
	 * Schedule the next run of the cron for a queue.
	 *
	 * Note: this does not check if the cron event is already scheduled.
	 *
	 * @param string $queue Queue name.
	 * @param int    $delay Delay in seconds, defaults to none.
	 */
	public static function schedule( string $queue = null, int $delay = 0 ): bool {
		if ( ! $queue ) {
			$queue = 'default';
		}

		$schedule = \wp_schedule_single_event( time() + $delay, static::EVENT, [ $queue, time() + $delay ], true );

		if ( is_wp_error( $schedule ) ) {
			dump( $schedule );

			return false;
		}

		return true;
	}

	/**
	 * Unschedule the next run of the cron for a queue.
	 *
	 * @param string $queue Queue name.
	 */
	public static function unschedule( string $queue = null ): void {
		static::get_scheduled_cron_jobs( $queue )
			->each(
				fn ( array $job ) => wp_unschedule_event( $job['timestamp'], static::EVENT, $job['args'] ?? [] ),
			);
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

		/**
		 * Provider instance.
		 *
		 * @var \Mantle\Contracts\Queue\Provider
		 */
		$provider = app( 'queue' )->get_provider( 'wordpress' );

		$pending_count = $provider->pending_count( $queue );

		// Ensure the queue job isn't scheduled if there are no items in the queue.
		if ( ! $pending_count ) {
			static::unschedule( $queue );

			return false;
		}

		$max_concurrent_batches  = max( 1, static::get_configuration_value( 'max_concurrent_batches', $queue, 1 ) );
		$batch_size              = static::get_configuration_value( 'batch_size', $queue, 100 );
		$already_scheduled_count = static::get_scheduled_count( $queue );

		// If there are already enough batches scheduled, don't schedule another.
		if ( $already_scheduled_count >= $max_concurrent_batches ) {
			return false;
		}

		$to_schedule = max( $max_concurrent_batches, ceil( $pending_count / $batch_size ) ) - $already_scheduled_count;

		if ( $to_schedule > 0 ) {
			$delay = static::get_configuration_value( 'delay', $queue, 0 );

			for ( $i = 0; $i < $to_schedule; $i++ ) {
				static::schedule( $queue, $delay );

				// Add a delay to the next scheduled queue job to stagger them and allow
				// for multiple of the same "job" to be scheduled. WordPress cron does
				// not allow for duplicate jobs to be scheduled by default.
				$delay += 5;
			}
		}

		return true;
	}

	/**
	 * Retrieve all the scheduled cron jobs for a queue from the cron API.
	 *
	 * @param string $queue Queue name.
	 * @return Collection<int, array>
	 */
	protected static function get_scheduled_cron_jobs( string $queue = null ): Collection {
		if ( ! $queue ) {
			$queue = 'default';
		}

		return collect( _get_cron_array() )
			->reduce(
				function ( Collection $carry, array $items, $timestamp ) use ( $queue ) {
					if ( empty( $items[ static::EVENT ] ) ) {
						return $carry;
					}

					foreach ( $items[ static::EVENT ] as $job ) {
						if ( empty( $job['args'][0] ) || $queue !== $job['args'][0] ) {
							continue;
						}

						$job['timestamp'] = $timestamp;

						$carry[] = $job;
					}

					return $carry;
				},
				collect(),
			);
	}

	/**
	 * Retrieve the number of already-scheduled queue jobs for a queue.
	 *
	 * @param string $queue Queue name.
	 */
	public static function get_scheduled_count( string $queue = null ): int {
		return static::get_scheduled_cron_jobs( $queue )->count();
	}

	/**
	 * Retrieve a configuration value for a queue.
	 *
	 * @param string $key Configuration key.
	 * @param string $queue Queue name.
	 * @param mixed  $default Default value.
	 */
	protected static function get_configuration_value( string $key, string $queue = null, mixed $default = null ): mixed {
		$config = config();

		// Check for a queue-specific configuration value.
		if ( $queue && $config->has( "queue.WordPress.queues.{$queue}.{$key}" ) ) {
			return $config->get( "queue.WordPress.queues.{$queue}.{$key}" );
		}

		// Check for a default configuration for the queue provider.
		if ( $config->has( "queue.WordPress.{$key}" ) ) {
			return $config->get( "queue.WordPress.{$key}" );
		}

		// Check for a default configuration for the queue configuration.
		if ( $config->has( "queue.{$key}" ) ) {
			return $config->get( "queue.{$key}" );
		}

		return $default;
	}
}
