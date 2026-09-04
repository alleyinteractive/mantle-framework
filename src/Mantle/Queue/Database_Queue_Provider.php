<?php
/**
 * Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Carbon\Carbon;
use DateTimeInterface;
use Laravel\SerializableClosure\SerializableClosure;
use Mantle\Contracts\Application;
use Mantle\Contracts\Queue\Provider as Contract;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Database\Query\Builder;
use Mantle\Queue\Jobs\Status;
use Mantle\Support\Collection;
use RuntimeException;

/**
 * WordPress Cron Queue Provider
 */
class Database_Queue_Provider implements Contract {
	/**
	 * The name of the provider.
	 *
	 * @var string
	 */
	public const NAME = 'wordpress';

	/**
	 * Queue of cron jobs to process.
	 *
	 * @var array<int, array<mixed>>
	 */
	protected static $pending_queue = [];

	/**
	 * Process the pending queue items that were added before `init`.
	 */
	public static function process_pending_queue(): void {
		if ( empty( static::$pending_queue ) ) {
			return;
		}

		$manager = app( Queue_Manager::class );

		if ( $manager instanceof Queue_Manager ) {
			$provider = $manager->get_provider();

			foreach ( static::$pending_queue as $args ) {
				$provider->push( ...$args );
			}
		}
	}

	/**
	 * Constructor
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( protected Application $app ) {}

	/**
	 * Push a job to the queue.
	 *
	 * @todo Add unique ID support to prevent duplicate jobs.
	 *
	 * @throws RuntimeException Thrown on error inserting the job into the database.
	 *
	 * @param mixed       $job Job instance.
	 * @param string|null $queue Queue name, optional.
	 */
	public function push( mixed $job, ?string $queue = null ): bool {
		// Account for adding to the queue before 'init'.
		if ( ! \did_action( 'init' ) ) {
			static::$pending_queue[] = func_get_args();

			return true;
		}

		// Resolve the queue and scheduled date from the job before it is
		// serialized, otherwise the property reads would be against a string.
		$queue ??= $job->queue ?? 'default';
		$now     = now()->toDateTimeString();
		$date    = match ( true ) {
			isset( $job->delay ) && $job->delay instanceof DateTimeInterface => Carbon::instance( $job->delay )->toDateTimeString(),
			isset( $job->delay ) && is_int( $job->delay ) => now()->addSeconds( $job->delay )->toDateTimeString(),
			default => now()->toDateTimeString(),
		};

		if ( $job instanceof SerializableClosure ) {
			$job = serialize( $job ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		}

		$object = new Jobs\Database_Job_Record( [
			'action'             => maybe_serialize( $job ),
			'type'               => get_debug_type( $job ),
			'available_at_gmt'   => $date,
			'created_date_gmt'   => $now,
			'queue'              => $queue,
			'scheduled_date_gmt' => $date,
		] );

		return $object->save();
	}

	/**
	 * Get the next set of job(s) in the queue.
	 *
	 * Candidate jobs are over-fetched and then claimed one at a time with an
	 * atomic, status-guarded UPDATE so that concurrent workers can never pop the
	 * same job twice. Jobs left in the running state by a crashed worker are
	 * reclaimed once their lock has expired.
	 *
	 * @todo Review the return types and consider adding a generic.
	 *
	 * @param string $queue Queue name.
	 * @param int    $count Number of items to fetch.
	 * @return Collection<int, \Mantle\Queue\Jobs\Database_Job>
	 * @phpstan-ignore method.childReturnType
	 */
	public function pop( ?string $queue = null, int $count = 1 ): Collection {
		$max_concurrent_batches = max( 1, $this->app['config']->get( 'queue.max_concurrent_batches', 1 ) );

		$candidates = $this->query( $queue )
			// Consider pending jobs as well as running jobs whose lock has expired
			// (i.e. left behind by a crashed worker). Currently-locked running jobs
			// keep their lock time in the future and are excluded below.
			->whereIn( 'status', [ Status::PENDING->value, Status::RUNNING->value ] )
			// Filter out jobs that are not scheduled to run yet or are still locked.
			->where_raw( 'available_at_gmt', '<=', now()->toDateTimeString() )
			// Over-fetch relative to the batch size so that we still have enough
			// candidates left after any are claimed by a concurrent worker.
			->take( $count * $max_concurrent_batches )
			->get();

		$claimed = new Collection();

		foreach ( $candidates as $candidate ) {
			if ( $claimed->count() >= $count ) {
				break;
			}

			$job   = new Jobs\Database_Job( $candidate );
			$inner = $job->get_job();

			// Lock the job until the configured timeout while it is processed. A
			// positive timeout is required so the new lock is always in the future.
			$timeout = is_object( $inner ) && isset( $inner->timeout ) ? max( 1, (int) $inner->timeout ) : 600;

			// Atomically claim the job, skipping it if another worker won the race.
			if ( $candidate->claim( now()->addSeconds( $timeout ) ) ) {
				$claimed->push( $job );
			}
		}

		return $claimed;
	}

	/**
	 * Retrieve the number of pending jobs in the queue.
	 *
	 * @param string|null $queue Queue name, optional.
	 */
	public function size( ?string $queue = null ): int {
		return $this->query( $queue )->count();
	}

	/**
	 * Retrieve the number of pending jobs in the queue.
	 *
	 * @param string|null $queue Queue name, optional.
	 */
	public function pending_size( ?string $queue = null ): int {
		return $this->query( $queue )
			->where( 'status', Status::PENDING->value )
			->count();
	}

	/**
	 * Construct the query builder for the queue.
	 *
	 * @param string|null $queue Queue name, optional.
	 * @return Builder<Jobs\Database_Job_Record>
	 */
	protected function query( ?string $queue = null ): Builder {
		return Jobs\Database_Job_Record::query()
			->where( 'queue', $queue ?? 'default' )
			->order_by( 'scheduled_date_gmt', 'asc' );
	}

	/**
	 * Check if a job is in the queue.
	 *
	 * Note: This does not handle delayed jobs well as you will be comparing
	 * serialized values.
	 *
	 * @param object $job Job instance.
	 * @param string $queue Queue to compare against.
	 */
	public function in_queue( mixed $job, ?string $queue = null ): bool {
		return Jobs\Database_Job_Record::query()
			->when(
				$queue,
				fn ( $query ) => $query->where( 'queue', $queue )
			)
			->where( 'type', get_debug_type( $job ) )
			->where( 'action', maybe_serialize( $job ) )
			->where( 'status', Status::PENDING->value )
			->exists();
	}
}
