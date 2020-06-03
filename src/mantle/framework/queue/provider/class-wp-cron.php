<?php
namespace Mantle\Framework\Queue\Provider;

use Mantle\Framework\Contracts\Queue\Provider;

/**
 * WordPress Cron Queue Provider
 */
class Wp_Cron implements Provider {
	/**
	 * Post type name for the queue.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'mantle_queue';

	/**
	 * Register the provider.
	 */
	public static function register() {
		\add_action( 'init', [ static::class, 'on_init' ] );
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
	}

	/**
	 * Push a job to the queue.
	 *
	 * @param mixed $job Job instance.
	 * @param int $delay Delay in seconds, optional.
	 * @return bool
	 */
	public function push( $job, int $delay = null ) {
		//
	}

	/**
	 * Get the next job in the queue.
	 *
	 * @return mixed|false
	 */
	public function pop() {
		//
	}
}
