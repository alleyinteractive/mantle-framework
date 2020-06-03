<?php
/**
 * Wp_Cron class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue\Provider;

use Mantle\Framework\Contracts\Queue\Provider;
use Mantle\Framework\Contracts\Queue\Queue_Manager;

/**
 * WordPress Cron Queue Provider
 *
 * Supports adding cron items to a general WordPress cron event run every
 * five minutes. The cron event will process a small batch of queue items.
 *
 * @todo Add support for one off cron events that should have their own cron scheduled.
 * @todo Add support for different queue names.
 */
class Wp_Cron implements Provider {
	/**
	 * Cron event.
	 *
	 * @var string
	 */
	public const CRON_EVENT = 'mantle_queue';

	/**
	 * Post type name for the queue.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'mantle_queue';

	/**
	 * Queue of cron jobs to process.
	 *
	 * @var array
	 */
	protected static $pending_queue = [];

	/**
	 * Register the provider.
	 */
	public static function register() {
		\add_action( 'init', [ static::class, 'on_init' ] );
		\add_action( static::CRON_EVENT, [ static::class, 'on_queue_run' ] );
	}

	/**
	 * 'init' callback.
	 */
	public static function on_init() {
		\register_post_type(
			static::POST_TYPE,
			[
				'public' => false,
			]
		);

		static::process_pending_queue();
		static::schedule_cron_event();
	}

	/**
	 * Callback for the cron event.
	 *
	 * @todo Abstract this a bit.
	 */
	public static function on_queue_run() {
		mantle_app( 'queue.worker' )->run_batch( 5 );
	}

	/**
	 * Schedule the next cron event.
	 *
	 * @return void
	 */
	protected static function schedule_cron_event() {
		if ( ! \wp_next_scheduled( static::CRON_EVENT ) ) {
			\wp_schedule_single_event( time() + ( MINUTE_IN_SECONDS * 5 ), static::CRON_EVENT );
		}
	}

	/**
	 * Process the pending queue items that we added before `init`.
	 */
	protected static function process_pending_queue() {
		if ( ! empty( static::$pending_queue ) ) {
			$manager = mantle_app( Queue_Manager::class );

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
	 * @todo Support priority sorting with `menu_order`.
	 *
	 * @param mixed $job Job instance.
	 * @param int   $delay Delay in seconds, optional.
	 * @return bool
	 */
	public function push( $job, int $delay = null ) {
		// Account for adding to the queue before 'init'.
		if ( ! \did_action( 'init' ) ) {
			static::$pending_queue[] = func_get_args();
			return true;
		}

		$insert = \wp_insert_post(
			[
				'post_type'   => static::POST_TYPE,
				'post_name'   => 'mantle_queue_' . time(),
				'post_status' => 'publish',
				'meta_input'  => [
					'_mantle_queue' => $job,
				],
			]
		);

		return ! is_wp_error( $insert );
	}

	/**
	 * Get the next job in the queue.
	 *
	 * @return mixed|false
	 */
	public function pop() {
		$post_ids = \get_posts(
			[
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'post_type'           => static::POST_TYPE,
				'posts_per_page'      => 1,
				'suppress_filters'    => false,
				'post_status'         => 'publish',
			]
		);

		if ( empty( $post_ids ) ) {
			return null;
		}

		$post_id = array_shift( $post_ids );
		$job     = \get_post_meta( $post_id, '_mantle_queue', true );

		// Remove the queue item.
		wp_delete_post( $post_id, true );

		return $job;
	}
}
