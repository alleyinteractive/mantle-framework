<?php
/**
 * Provider interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Queue;

use Mantle\Support\Collection;

/**
 * Queue Provider Contract
 */
interface Provider {
	/**
	 * Push a job to the queue.
	 *
	 * @param mixed $job Job instance.
	 * @return bool
	 */
	public function push( $job );

	/**
	 * Get the next set of jobs in the queue.
	 *
	 * @param string $queue Queue name, optional.
	 * @param int    $count Number of items to return.
	 * @return Collection
	 */
	public function pop( string $queue = null, int $count = 1 ): Collection;

	/**
	 * Check if a job is in the queue.
	 *
	 * @param mixed  $job Job instance.
	 * @param string $queue Queue to compare against.
	 * @return bool
	 */
	public function in_queue( $job, string $queue = null ): bool;
}
