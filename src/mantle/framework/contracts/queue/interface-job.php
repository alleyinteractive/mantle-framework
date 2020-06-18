<?php
/**
 * Job interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Queue;

/**
 * Job interface.
 */
interface Job {
	/**
	 * Handle the job.
	 */
	public function handle();
}
