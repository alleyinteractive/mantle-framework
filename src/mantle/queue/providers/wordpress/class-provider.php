<?php
/**
 * Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

use InvalidArgumentException;
use Laravel\SerializableClosure\SerializableClosure;
use Mantle\Contracts\Queue\Provider as Provider_Contract;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Support\Collection;
use RuntimeException;

use function Mantle\Support\Helpers\collect;

/**
 * WordPress Cron Queue Provider
 *
 * @todo Add support for one off cron events that should have their own cron scheduled.
 * @todo Add support for job locks.
 * @todo Add support for delayed jobs.
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

		$queue = $job->queue ?? 'default';

		$object = new Queue_Job( [
			'post_name'   => 'mantle_queue_' . time(),
			'post_status' => Post_Status::PENDING->value,
		] );

		$object->meta->_mantle_queue = $job;

		$object->save();

		$object->set_terms(
			[
				static::OBJECT_NAME => static::get_queue_term_id( $queue ),
			]
		);

		return true;
	}

	/**
	 * Get the next set of jobs in the queue.
	 *
	 * @param string $queue Queue name.
	 * @param int    $count Number of items to fetch.
	 * @return Collection
	 */
	public function pop( string $queue = null, int $count = 1 ): Collection {
		return Queue_Job::query()
			->where( 'post_status', Post_Status::PENDING->value )
			->whereTerm( static::get_queue_term_id( $queue ), static::OBJECT_NAME )
			->orderBy( 'id', 'asc' )
			->take( $count )
			->get()
			->map(
				fn ( Queue_Job $job ) => new Queue_Worker_Job( $job->meta->_mantle_queue, $job->ID ),
			);
	}

	/**
	 * Retrieve the number of pending jobs in the queue.
	 *
	 * @param string $queue Queue name, optional.
	 * @return int
	 */
	public function pending_count( string $queue = null ): int {
		return Queue_Job::where( 'post_status', Post_Status::PENDING->value )
			->whereTerm( static::get_queue_term_id( $queue ), static::OBJECT_NAME )
			->count();
	}

	/**
	 * Check if a job is in the queue.
	 *
	 * @param object $job Job instance.
	 * @param string $queue Queue to compare against.
	 * @return bool
	 */
	public function in_queue( mixed $job, string $queue = null ): bool {
		return Queue_Job::where( 'post_status', Post_Status::PENDING->value )
			->whereTerm( static::get_queue_term_id( $queue ), static::OBJECT_NAME )
			->whereMeta( '_mantle_queue', maybe_serialize( $job ) )
			->take( 1 )
			->get()
			->is_not_empty();
	}

	/**
	 * Get the taxonomy term for a queue.
	 *
	 * @param string $name Queue name, optional.
	 * @return int
	 *
	 * @throws InvalidArgumentException Thrown on invalid queue term.
	 */
	public static function get_queue_term_id( string $name = null ): int {
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
