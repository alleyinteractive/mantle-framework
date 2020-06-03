<?php
namespace Mantle\Framework\Contracts\Queue;

interface Provider {
	/**
	 * Register the provider.
	 */
	public static function register();

	/**
	 * Push a job to the queue.
	 *
	 * @param mixed $job Job instance.
	 * @param int $delay Delay in seconds, optional.
	 * @return bool
	 */
	public function push( $job, int $delay = null );

	/**
	 * Get the next job in the queue.
	 *
	 * @return mixed|false
	 */
	public function pop();
}
