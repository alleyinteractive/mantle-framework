<?php
/**
 * Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;
use Laravel\SerializableClosure\SerializableClosure;
use Mantle\Contracts\Application;
use Mantle\Contracts\Queue\Provider as Provider_Contract;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use RuntimeException;

/**
 * WordPress Cron Queue Provider
 *
 * @todo Add support for one off cron events that should have their own cron scheduled.
 */
class Provider implements Provider_Contract {
	/**
	 * Post/taxonomy name for the internal queue.
	 *
	 * @var string
	 */
	public const OBJECT_NAME = 'mantle_queue';

	/**
	 * Queue of cron jobs to process.
	 *
	 * @var array<int, array<mixed>>
	 */
	protected static $pending_queue = [];

	/**
	 * Register the data types on 'init'.
	 */
	public static function register_data_types(): void {
		\register_post_type(
			static::OBJECT_NAME,
			[
				'public' => false,
			]
		);

		\register_taxonomy(
			static::OBJECT_NAME,
			static::OBJECT_NAME,
			[
				'public' => false,
			]
		);

		foreach ( Post_Status::cases() as $status ) {
			\register_post_status(
				$status->value,
				[
					'internal' => true,
					'public'   => false,
				]
			);
		}

		static::process_pending_queue();
	}

	/**
	 * Process the pending queue items that were added before `init`.
	 */
	protected static function process_pending_queue(): void {
		if ( ! empty( static::$pending_queue ) ) {
			$manager = app( Queue_Manager::class );

			if ( $manager ) {
				$provider = $manager->get_provider();

				foreach ( static::$pending_queue as $args ) {
					$provider->push( ...$args );
				}
			}
		}
	}

	/**
	 * Constructor
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( protected Application $app ) {
	}

	/**
	 * Push a job to the queue.
	 *
	 * @throws RuntimeException Thrown on error inserting the job into the database.
	 *
	 * @param mixed $job Job instance.
	 * @return bool
	 */
	public function push( mixed $job ): bool {
		// Account for adding to the queue before 'init'.
		if ( ! \did_action( 'init' ) ) {
			static::$pending_queue[] = func_get_args();

			return true;
		}

		if ( $job instanceof SerializableClosure ) {
			$job = serialize( $job ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		}

		$queue    = $job->queue ?? 'default';
		$job_name = Str::of( $job::class )
			->replace( '\\', '_' )
			->replace( '_', '-' )
			->lower()
			->slug();

		$object = new Queue_Record(
			[
				'post_name'   => "mantle_queue_{$job_name}_" . time(),
				'post_status' => Post_Status::PENDING->value,
				'meta'        => [
					Meta_Key::JOB->value        => $job,
					Meta_Key::START_TIME->value => now()->getTimestamp(),
				],
			]
		);

		// Handle the job being delayed.
		if ( isset( $job->delay ) ) {
			// Translate the delay into a timestamp.
			$delay = $job->delay instanceof DateTimeInterface
				? $job->delay->getTimestamp()
				: now()->addSeconds( $job->delay )->getTimestamp();

			// Set the post date to the timestamp of the delay to prevent it from
			// being started before the delay.
			$object->post_date = Carbon::createFromTimestamp( $delay, wp_timezone() )->toDateTimeString();
		}

		$object->save();

		// TODO: Convert this to a queued term setter like we do with meta.
		$object->set_terms(
			[
				static::OBJECT_NAME => static::get_queue_term_id( $queue ),
			]
		);

		return true;
	}

	/**
	 * Get the next set of job(s) in the queue.
	 *
	 * @todo Lock the jobs after popping them off the queue.
	 *
	 * @param string $queue Queue name.
	 * @param int    $count Number of items to fetch.
	 * @return Collection<int, \Mantle\Queue\Providers\WordPress\Queue_Worker_Job>
	 */
	public function pop( string $queue = null, int $count = 1 ): Collection {
		$max_concurrent_batches = max( 1, $this->app['config']->get( 'queue.max_concurrent_batches', 1 ) );

		return $this->query( $queue )
			// Ensure the we're only retrieving jobs that are not scheduled for the
			// future ordered by the oldest first.
			->olderThanOrEqualTo( now() )
			->orderBy( 'post_date', 'asc' )
			// Multiply the count times the number of concurrent batches to get the
			// number of jobs to fetch. This accounts for job locks without needing a
			// meta query.
			->take( $count * $max_concurrent_batches )
			->get()
			// Filter out any jobs that are locked.
			->filter( fn ( Queue_Record $record ) => ! $record->is_locked() )
			->map(
				fn ( Queue_Record $record ) => tap(
					new Queue_Worker_Job( $record ),
					// Lock the job until the configured timeout or 10 minutes.
					fn ( Queue_Worker_Job $job ) => $record->set_lock_until(
						$job->get_job()->timeout ?? 600
					),
				),
			)
			->take( $count )
			->values();
	}

	/**
	 * Retrieve the number of pending jobs in the queue.
	 *
	 * @param string $queue Queue name, optional.
	 * @return int
	 */
	public function pending_count( string $queue = null ): int {
		return $this->query( $queue )->count();
	}

	/**
	 * Construct the query builder for the queue.
	 *
	 * @param string|null $queue Queue name, optional.
	 * @return Post_Query_Builder<Queue_Record>
	 */
	protected function query( string $queue = null ): Post_Query_Builder {
		return Queue_Record::where( 'post_status', Post_Status::PENDING->value )
			->whereTerm( static::get_queue_term_id( $queue ), static::OBJECT_NAME )
			->orderBy( 'post_date', 'asc' );
	}

	/**
	 * Check if a job is in the queue.
	 *
	 * @param object $job Job instance.
	 * @param string $queue Queue to compare against.
	 * @return bool
	 */
	public function in_queue( mixed $job, string $queue = null ): bool {
		return Queue_Record::where( 'post_status', Post_Status::PENDING->value )
			->whereDate( now()->toDateTimeString(), '>=' )
			->whereTerm( static::get_queue_term_id( $queue ), static::OBJECT_NAME )
			->whereMeta( Meta_Key::JOB->value, maybe_serialize( $job ) )
			->exists();
	}

	/**
	 * Get the taxonomy term for a queue.
	 *
	 * @param string|null $name Queue name, optional.
	 * @return int
	 *
	 * @throws InvalidArgumentException Thrown on invalid queue term.
	 */
	public static function get_queue_term_id( ?string $name = null ): int {
		if ( ! $name ) {
			$name = 'default';
		}

		$term = \get_term_by( 'slug', $name, static::OBJECT_NAME );

		if ( empty( $term ) ) {
			$insert = \wp_insert_term( $name, static::OBJECT_NAME, [ 'slug' => $name ] );

			if ( is_wp_error( $insert ) ) {
				throw new InvalidArgumentException( 'Error creating queue term: ' . $insert->get_error_message() );
			}

			return $insert['term_id'];
		}

		return $term->term_id;
	}
}
