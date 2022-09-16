<?php
/**
 * Wp_Cron class file.
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
 * Supports adding cron items to a general WordPress cron event run every
 * five minutes. The cron event will process a small batch of queue items.
 *
 * @todo Add support for one off cron events that should have their own cron scheduled.
 * @todo Add support for different queue names.
 */
class Provider implements Provider_Contract {
	/**
	 * Post/taxonomy name for the internal queue.
	 *
	 * @var string
	 */
	public const OBJECT_NAME = 'mantle_queue';

	/**
	 * Post status for failed jobs.
	 *
	 * @var string
	 */
	public const POST_STATUS_FAILED = 'failed';

	/**
	 * Queue of cron jobs to process.
	 *
	 * @var array
	 */
	protected static $pending_queue = [];

	/**
	 * 'init' callback.
	 */
	public static function on_init() {
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

		\register_post_status(
			static::POST_STATUS_FAILED,
			[
				'internal' => true,
				'public'   => false,
			]
		);

		static::process_pending_queue();
	}

	/**
	 * Process the pending queue items that were added before `init`.
	 */
	protected static function process_pending_queue() {
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
	public function push( $job ) {
		// Account for adding to the queue before 'init'.
		if ( ! \did_action( 'init' ) ) {
			static::$pending_queue[] = func_get_args();
			return true;
		}

		if ( $job instanceof SerializableClosure ) {
			$job = serialize( $job ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		}

		$queue  = $job->queue ?? 'default';
		$insert = \wp_insert_post(
			[
				'post_type'   => static::OBJECT_NAME,
				'post_name'   => 'mantle_queue_' . time(),
				'post_status' => 'publish',
				'meta_input'  => [
					'_mantle_queue' => $job,
				],
			]
		);

		if ( is_wp_error( $insert ) ) {
			throw new RuntimeException( 'Error adding job to queue: ' . $insert->get_error_message() );
		}

		wp_set_object_terms( $insert, static::get_queue_term_id( $queue ), static::OBJECT_NAME, false );

		// Ensure that the next cron event is scheduled for this queue.
		Scheduler::schedule( $queue );

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
		$post_ids = \get_posts(
			[
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'order'               => 'ASC',
				'orderby'             => 'date',
				'post_status'         => 'publish',
				'post_type'           => static::OBJECT_NAME,
				'posts_per_page'      => $count,
				'suppress_filters'    => false,
				'tax_query'           => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => static::OBJECT_NAME,
						'terms'    => static::get_queue_term_id( $queue ),
					],
				],
			]
		);

		if ( empty( $post_ids ) ) {
			return collect();
		}

		return collect( $post_ids )
			->map(
				fn( int $post_id ) => new Queue_Worker_Job( \get_post_meta( $post_id, '_mantle_queue', true ), $post_id ),
			);
	}

	/**
	 * Check if a job is in the queue.
	 *
	 * @param object $job Job instance.
	 * @param string $queue Queue to compare against.
	 * @return bool
	 */
	public function in_queue( $job, string $queue = null ): bool {
		$queued_objects = \get_posts(
			[
				'fields'         => 'ids',
				'post_status'    => 'publish',
				'post_type'      => static::OBJECT_NAME,
				'posts_per_page' => 100,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => '_mantle_queue',
						'value' => maybe_serialize( $job ),
					],
				],
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => static::OBJECT_NAME,
						'terms'    => static::get_queue_term_id( $queue ),
					],
				],
			]
		);

		return ! empty( $queued_objects );
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
