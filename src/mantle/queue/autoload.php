<?php
/**
 * Queue Helpers
 *
 * @package Mantle
 */

namespace Mantle\Queue;

if ( ! function_exists( 'dispatch' ) ) {
	/**
	 * Dispatch a job to the queue.
	 *
	 * @param \Mantle\Contracts\Queue\Job|\Closure $job Job instance.
	 * @return Pending_Dispatch|Pending_Closure_Dispatch
	 */
	function dispatch( $job ): Pending_Dispatch {
		return $job instanceof \Closure
			? new Pending_Closure_Dispatch( Closure_Job::create( $job ) )
			: new Pending_Dispatch( $job );
	}
}
