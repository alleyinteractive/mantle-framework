<?php
/**
 * Dispatchable class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Contracts\Queue\Dispatcher;

/**
 * Allow a job to be dispatchable.
 */
trait Dispatchable {
	/**
	 * Dispatch the job with the given arguments.
	 *
	 * @param mixed ...$args Arguments passed to the job.
	 * @return Pending_Dispatch
	 */
	public static function dispatch( ...$args ): Pending_Dispatch {
		return new Pending_Dispatch( new static( ...$args ) );
	}

	/**
	 * Dispatch the job with the given arguments if the given truth test passes.
	 *
	 * @param bool  $boolean Truth check.
	 * @param mixed ...$args Dispatch arguments.
	 * @return Pending_Dispatch|false
	 */
	public static function dispatch_if( $boolean, ...$args ): Pending_Dispatch|bool {
		return $boolean ? static::dispatch( ...$args ) : false;
	}

	/**
	 * Dispatch the job with the given arguments if the given truth test passes.
	 *
	 * @param bool  $boolean Truth check.
	 * @param mixed ...$args Dispatch arguments.
	 * @return Pending_Dispatch|false
	 */
	public static function dispatch_unless( $boolean, ...$args ): Pending_Dispatch|bool {
		return ! $boolean ? static::dispatch( ...$args ) : false;
	}

	/**
	 * Dispatch a job now given the current arguments.
	 *
	 * @param mixed ...$args Dispatch arguments.
	 */
	public static function dispatch_now( ...$args ): void {
		app( Dispatcher::class )->dispatch_now(
			new static( ...$args )
		);
	}
}
