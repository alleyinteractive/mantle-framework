<?php
/**
 * Interacts_With_Time trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Carbon\CarbonImmutable;
use Closure;
use Mantle\Support\Carbon;

/**
 * Interactions with time.
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Interacts_With_Time {
	/**
	 * Set up time interaction for the test.
	 */
	public function interacts_with_time_tear_down(): void {
		$this->travel_back();

		CarbonImmutable::setTestNow();
	}

	/**
	 * Set the current time to a specific date.
	 *
	 * @param \DateTimeInterface|\Closure|null $date
	 */
	public function travel( \DateTimeInterface|Closure|null $date = null ): void {
		Carbon::setTestNow( $date );
	}

	/**
	 * Travel to another time for a given callback.
	 *
	 * Time is reset back to current time after the callback is executed.
	 *
	 * @param \DateTimeInterface|\Closure|Carbon|string|null $date
	 * @param callable                                       $callback
	 */
	public function travel_to( \DateTimeInterface|\Closure|Carbon|string|null $date, callable $callback ): void {
		Carbon::setTestNow( $date );

		$callback( $date );

		Carbon::setTestNow( null );
	}

	/**
	 * Travel back to the current time.
	 */
	public function travel_back(): void {
		Carbon::setTestNow( null );
	}
}
