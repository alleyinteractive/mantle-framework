<?php
/**
 * Queue class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Mantle\Facade\Facade;
use Mantle\Queue\Queue_Fake;

/**
 * Queue Facade
 *
 * @method static void dispatch(mixed $job)
 * @method static void dispatch_after_response(mixed $job)
 * @method static void dispatch_now(mixed $job)
 *
 * @see \Mantle\Queue\Dispatcher
 */
class Queue extends Facade {
	/**
	 * Replace the bound instance with a fake.
	 *
	 * @throws \RuntimeException If no Mantle application instance has been set.
	 */
	public static function fake(): \Mantle\Queue\Queue_Fake {
		if ( ! isset( static::$app ) ) {
			throw new \RuntimeException( 'A Mantle application instance has not been set.' );
		}

		$fake = new Queue_Fake( static::$app );

		static::swap( $fake );

		return $fake;
	}

	/**
	 * Get the registered name of the component.
	 */
	protected static function get_facade_accessor(): string {
		return 'queue';
	}
}
