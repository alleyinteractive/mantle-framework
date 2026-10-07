<?php
/**
 * Database_Scheduler class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Carbon\Carbon;
use Mantle\Config\Repository;
use Mantle\Support\Collection;

use function Mantle\Support\Helpers\collect;

/**
 * Database Cron Scheduler
 *
 * Scheduler for the cron that is powered by a database table and the WordPress
 * Cron API.
 */
class Database_Scheduler {
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
	 * Constructor.
	 *
	 * @param Queue_Manager $manager Queue manager instance.
	 * @param Worker        $worker Queue worker instance.
	 * @param Repository    $config Configuration repository instance.
	 */
	public function __construct( protected Queue_Manager $manager, protected Worker $worker, protected Repository $config ) {}

	/**
	 * Handle a job being queued and ensure the cron is scheduled for the queue on shutdown.
	 *
	 * @param string $queue Queue name.
	 */
	public function handle_job_queued( string $queue ): void {
		if ( ! in_array( $queue, static::$pending_queues, true ) ) {
			static::$pending_queues[] = $queue;
		}

		if ( ! has_action( 'shutdown', [ $this, 'schedule_on_shutdown' ] ) ) {
			add_action( 'shutdown', [ $this, 'schedule_on_shutdown' ], 1 );
		}
	}

	/**
	 * Schedule the next run of the queue on shutdown for all pending queues.
	 *
	 * Once a queue job is dispatched, the queue will be scheduled to run on
	 * shutdown to deduplicate any scheduling calls.
	 */
	public function schedule_on_shutdown(): void {
		foreach ( static::$pending_queues as $pending_queue ) {
			$this->schedule_next_run( $pending_queue );
		}

		static::$pending_queues = [];
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
	public function schedule_next_run( ?string $queue = null ): bool {
		if ( ! $queue ) {
			$queue = 'default';
		}

		$provider = $this->manager->get_provider( Database_Queue_Provider::NAME );

		if ( ! $provider instanceof Database_Queue_Provider ) {
			return false;
		}

		$pending_size = $provider->available_size( $queue );

		// Nothing can run yet, so schedule a single run for when the next job becomes available.
		if ( 0 === $pending_size ) {
			$next = $provider->next_available_at( $queue );

			if ( ! $next instanceof Carbon ) {
				$this->unschedule( $queue );

				return false;
			}

			if ( static::get_scheduled_count( $queue, $next->getTimestamp() ) > 0 ) {
				return false;
			}

			return $this->schedule( $queue, max( 0, $next->getTimestamp() - time() ) );
		}

		$max_concurrent_batches = max( 1, $this->get_configuration_value( 'max_concurrent_batches', $queue, 1 ) );
		$batch_size             = $this->get_configuration_value( 'batch_size', $queue, 100 );
		$delay                  = $this->get_configuration_value( 'delay', $queue, 0 );

		// Runs held back for delayed jobs don't count, otherwise they would block jobs available now.
		$already_scheduled_count = static::get_scheduled_count( $queue, time() + $delay + ( 5 * $max_concurrent_batches ) );

		// If there are already enough batches scheduled, don't schedule another.
		if ( $already_scheduled_count >= $max_concurrent_batches ) {
			return false;
		}

		$runs_needed = ceil( $pending_size / $batch_size ) - $already_scheduled_count;

		// Ensure we don't schedule more than the maximum number of concurrent batches.
		if ( $runs_needed > $max_concurrent_batches ) {
			$runs_needed = $max_concurrent_batches;
		}

		if ( $runs_needed > 0 ) {
			for ( $i = 0; $i < $runs_needed; $i++ ) {
				$this->schedule( $queue, $delay );

				// Add a delay to the next scheduled queue job to stagger them and allow
				// for multiple of the same "job" to be scheduled. WordPress cron does
				// not allow for duplicate jobs to be scheduled by default.
				$delay += 5;
			}
		}

		return true;
	}

	/**
	 * Callback for the cron event.
	 *
	 * @param string $queue Queue name, optional.
	 */
	public function run( ?string $queue = null ): void {
		if ( ! $queue ) {
			$queue = 'default';
		}

		wp_raise_memory_limit( 'cron' );

		$this->worker->run( $this->get_configuration_value( 'batch_size', $queue, 100 ), $queue );
	}

	/**
	 * Schedule the next run of the cron for a queue.
	 *
	 * Note: this does not check if the cron event is already scheduled.
	 *
	 * @param string $queue Queue name.
	 * @param int    $delay Delay in seconds, defaults to none.
	 */
	public function schedule( ?string $queue = null, int $delay = 0 ): bool {
		if ( ! $queue ) {
			$queue = 'default';
		}

		$schedule = \wp_schedule_single_event( time() + $delay, static::EVENT, [ $queue, time() + $delay ], true );

		return ! is_wp_error( $schedule );
	}

	/**
	 * Unschedule the next run of the cron for a queue.
	 *
	 * @param string $queue Queue name.
	 */
	public function unschedule( ?string $queue = null ): void {
		static::get_scheduled_cron_jobs( $queue )
			->each(
				fn ( array $job ) => wp_unschedule_event( $job['timestamp'], static::EVENT, $job['args'] ?? [] ),
			);
	}

	/**
	 * Retrieve all the scheduled cron jobs for a queue from WordPress' cron API.
	 *
	 * @param string $queue Queue name.
	 * @return Collection<int, array<mixed>>
	 */
	protected static function get_scheduled_cron_jobs( ?string $queue = null ): Collection {
		if ( ! $queue ) {
			$queue = 'default';
		}

		$jobs = [];

		foreach ( (array) _get_cron_array() as $timestamp => $items ) {
			if ( empty( $items[ static::EVENT ] ) ) {
				continue;
			}

			foreach ( $items[ static::EVENT ] as $job ) {
				if ( ! is_array( $job ) || empty( $job['args'][0] ) || $queue !== $job['args'][0] ) {
					continue;
				}

				$job['timestamp'] = $timestamp;

				$jobs[] = $job;
			}
		}

		return collect( $jobs ); // @phpstan-ignore-line return.type
	}

	/**
	 * Retrieve the number of already-scheduled queue jobs for a queue.
	 *
	 * @param string   $queue  Queue name.
	 * @param int|null $before Only count runs scheduled at or before this timestamp.
	 */
	public static function get_scheduled_count( ?string $queue = null, ?int $before = null ): int {
		return static::get_scheduled_cron_jobs( $queue )
			->filter( fn ( array $job ) => null === $before || $job['timestamp'] <= $before )
			->count();
	}

	/**
	 * Retrieve a configuration value for a queue.
	 *
	 * @param string $key Configuration key.
	 * @param string $queue Queue name.
	 * @param mixed  $default Default value.
	 */
	public function get_configuration_value( string $key, ?string $queue = null, mixed $default = null ): mixed {
		// Check for a queue-specific configuration value.
		if ( $queue && $this->config->has( "queue.wordpress.queues.{$queue}.{$key}" ) ) {
			return $this->config->get( "queue.wordpress.queues.{$queue}.{$key}" );
		}

		// Check for a default configuration for the queue provider.
		if ( $this->config->has( "queue.wordpress.{$key}" ) ) {
			return $this->config->get( "queue.wordpress.{$key}" );
		}

		// Check for a default configuration for the queue configuration.
		if ( $this->config->has( "queue.{$key}" ) ) {
			return $this->config->get( "queue.{$key}" );
		}

		return $default;
	}
}
