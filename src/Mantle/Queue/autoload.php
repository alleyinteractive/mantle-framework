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
	 * @template TJob of \Mantle\Contracts\Queue\Job|\Closure
	 *
	 * @param \Mantle\Contracts\Queue\Job|\Closure $job Job instance.
	 *
	 * @phpstan-param TJob|\Mantle\Contracts\Queue\Job|\Closure|\Closure $job Job instance.
	 * @phpstan-return (TJob is \Closure ? Pending_Closure_Dispatch : Pending_Dispatch)<TJob>
	 */
	function dispatch( $job ): Pending_Dispatch|Pending_Closure_Dispatch {
		return $job instanceof \Closure
			? new Pending_Closure_Dispatch( Closure_Job::create( $job ) )
			: new Pending_Dispatch( $job );
	}
}
