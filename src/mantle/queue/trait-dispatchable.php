<?php
/**
 * Dispatchable class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Framework\Contracts\Queue\Dispatcher;

/**
 * Allow a job to be dispatchable.
 */
trait Dispatchable {
	/**
	 * Dispatch the job with the given arguments.
	 *
	 * @param mixed ...$args Dispatch arguments.
	 */
	public static function dispatch( ...$args ) {
		return new Pending_Dispatch( new static( ...$args ) );
	}

	/**
	 * Dispatch the job with the given arguments if the given truth test passes.
	 *
	 * @param bool  $boolean Truth check.
	 * @param mixed ...$args Dispatch arguments.
	 */
	public static function dispatch_if( $boolean, ...$args ) {
		return $boolean ? static::dispatch( ...$args ) : false;
	}

	/**
	 * Dispatch the job with the given arguments if the given truth test passes.
	 *
	 * @param bool  $boolean Truth check.
	 * @param mixed ...$args Dispatch arguments.
	 */
	public static function dispatch_unless( $boolean, ...$args ) {
		return ! $boolean ? static::dispatch( ...$args ) : false;
	}

	/**
	 * Dispatch a job now given the current arguments.
	 *
	 * @param mixed ...$args Dispatch arguments.
	 */
	public static function dispatch_now( ...$args ) {
		return app( Dispatcher::class )->dispatch_now(
			new static( ...$args )
		);
	}
}
