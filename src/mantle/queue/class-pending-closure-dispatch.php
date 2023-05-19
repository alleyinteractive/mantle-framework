<?php
/**
 * Pending_Closure_Dispatch class file
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Closure;

/**
 * Pending Closure Dispatch
 *
 * Used to wrap a pending Closure job that will be dispatched to the queue.
 */
class Pending_Closure_Dispatch extends Pending_Dispatch {
	/**
	 * Add a callback to be executed on failure.
	 *
	 * @param Closure $callback Callback to invoke.
	 * @return static
	 */
	public function catch( Closure $callback ) {
		$this->job->on_failure( $callback );

		return $this;
	}
}
