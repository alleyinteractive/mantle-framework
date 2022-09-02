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
 */
class Queue extends Facade {
	/**
	 * Replace the bound instance with a fake.
	 *
	 * @return \Illuminate\Support\Testing\Fakes\QueueFake
	 */
	public static function fake() {
		$fake = new Queue_Fake( static::$app );

		static::swap( $fake );

		return $fake;
	}

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'queue';
	}
}
