<?php
/**
 * Manages_Frequencies trait file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Scheduling;

use Carbon\Carbon;

/**
 * Manages Frequency logic for scheduled events.
 *
 * Provides a very fluent interface to schedule a command to be run.
 */
trait Manages_Frequencies {

	/**
	 * The Cron expression representing the event's frequency.
	 *
	 * @param  string $expression
	 * @return static
	 */
	public function cron( $expression ) {
		$this->expression = $expression;

		return $this;
	}

	/**
	 * Schedule the event to run between start and end time.
	 *
	 * @param  string $start_time
	 * @param  string $end_time
	 * @return static
	 */
	public function between( $start_time, $end_time ) {
		return $this->when( $this->inTimeInterval( $start_time, $end_time ) );
	}

	/**
	 * Schedule the event to not run between start and end time.
	 *
	 * @param  string $start_time
	 * @param  string $end_time
	 * @return static
	 */
	public function unlessBetween( $start_time, $end_time ) {
		return $this->skip( $this->inTimeInterval( $start_time, $end_time ) );
	}

	/**
	 * Schedule the event to run between start and end time.
	 *
	 * @param  string $start_time
	 * @param  string $end_time
	 * @return \Closure
	 */
	private function inTimeInterval( $start_time, $end_time ) {
		[ $now, $start_time, $end_time ] = [
			Carbon::now( $this->timezone ),
			Carbon::parse( $start_time, $this->timezone ),
			Carbon::parse( $end_time, $this->timezone ),
		];

		if ( $end_time->lessThan( $start_time ) ) {
			if ( $start_time->greaterThan( $now ) ) {
				$start_time->subDay();
			} else {
				$end_time->addDay();
			}
		}

		return fn () => $now->between( $start_time, $end_time );
	}

	/**
	 * Schedule the event to run every minute.
	 *
	 * @return static
	 */
	public function everyMinute() {
		return $this->spliceIntoPosition( 1, '*' );
	}

	/**
	 * Schedule the event to run every two minutes.
	 *
	 * @return static
	 */
	public function everyTwoMinutes() {
		return $this->spliceIntoPosition( 1, '*/2' );
	}

	/**
	 * Schedule the event to run every three minutes.
	 *
	 * @return static
	 */
	public function everyThreeMinutes() {
		return $this->spliceIntoPosition( 1, '*/3' );
	}

	/**
	 * Schedule the event to run every four minutes.
	 *
	 * @return static
	 */
	public function everyFourMinutes() {
		return $this->spliceIntoPosition( 1, '*/4' );
	}

	/**
	 * Schedule the event to run every five minutes.
	 *
	 * @return static
	 */
	public function everyFiveMinutes() {
		return $this->spliceIntoPosition( 1, '*/5' );
	}

	/**
	 * Schedule the event to run every ten minutes.
	 *
	 * @return static
	 */
	public function everyTenMinutes() {
		return $this->spliceIntoPosition( 1, '*/10' );
	}

	/**
	 * Schedule the event to run every fifteen minutes.
	 *
	 * @return static
	 */
	public function everyFifteenMinutes() {
		return $this->spliceIntoPosition( 1, '*/15' );
	}

	/**
	 * Schedule the event to run every thirty minutes.
	 *
	 * @return static
	 */
	public function everyThirtyMinutes() {
		return $this->spliceIntoPosition( 1, '0,30' );
	}

	/**
	 * Schedule the event to run hourly.
	 *
	 * @return static
	 */
	public function hourly() {
		return $this->spliceIntoPosition( 1, 0 );
	}

	/**
	 * Schedule the event to run hourly at a given offset in the hour.
	 *
	 * @param  array|int $offset
	 * @return static
	 */
	public function hourlyAt( $offset ) {
		$offset = is_array( $offset ) ? implode( ',', $offset ) : $offset;

		return $this->spliceIntoPosition( 1, $offset );
	}

	/**
	 * Schedule the event to run every two hours.
	 *
	 * @return static
	 */
	public function everyTwoHours() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, '*/2' );
	}

	/**
	 * Schedule the event to run every three hours.
	 *
	 * @return static
	 */
	public function everyThreeHours() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, '*/3' );
	}

	/**
	 * Schedule the event to run every four hours.
	 *
	 * @return static
	 */
	public function everyFourHours() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, '*/4' );
	}

	/**
	 * Schedule the event to run every six hours.
	 *
	 * @return static
	 */
	public function everySixHours() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, '*/6' );
	}

	/**
	 * Schedule the event to run daily.
	 *
	 * @return static
	 */
	public function daily() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, 0 );
	}

	/**
	 * Schedule the command at a given time.
	 *
	 * @param  string $time
	 * @return static
	 */
	public function at( $time ) {
		return $this->dailyAt( $time );
	}

	/**
	 * Schedule the event to run daily at a given time (10:00, 19:30, etc).
	 *
	 * @param  string $time
	 * @return static
	 */
	public function dailyAt( $time ) {
		$segments = explode( ':', $time );

		return $this->spliceIntoPosition( 2, (int) $segments[0] )
			->spliceIntoPosition( 1, 2 === count( $segments ) ? (int) $segments[1] : '0' );
	}

	/**
	 * Schedule the event to run twice daily.
	 *
	 * @param  int $first
	 * @param  int $second
	 * @return static
	 */
	public function twiceDaily( $first = 1, $second = 13 ) {
		$hours = $first . ',' . $second;

		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, $hours );
	}

	/**
	 * Schedule the event to run only on weekdays.
	 *
	 * @return static
	 */
	public function weekdays() {
		return $this->spliceIntoPosition( 5, '1-5' );
	}

	/**
	 * Schedule the event to run only on weekends.
	 *
	 * @return static
	 */
	public function weekends() {
		return $this->spliceIntoPosition( 5, '0,6' );
	}

	/**
	 * Schedule the event to run only on Mondays.
	 *
	 * @return static
	 */
	public function mondays() {
		return $this->days( 1 );
	}

	/**
	 * Schedule the event to run only on Tuesdays.
	 *
	 * @return static
	 */
	public function tuesdays() {
		return $this->days( 2 );
	}

	/**
	 * Schedule the event to run only on Wednesdays.
	 *
	 * @return static
	 */
	public function wednesdays() {
		return $this->days( 3 );
	}

	/**
	 * Schedule the event to run only on Thursdays.
	 *
	 * @return static
	 */
	public function thursdays() {
		return $this->days( 4 );
	}

	/**
	 * Schedule the event to run only on Fridays.
	 *
	 * @return static
	 */
	public function fridays() {
		return $this->days( 5 );
	}

	/**
	 * Schedule the event to run only on Saturdays.
	 *
	 * @return static
	 */
	public function saturdays() {
		return $this->days( 6 );
	}

	/**
	 * Schedule the event to run only on Sundays.
	 *
	 * @return static
	 */
	public function sundays() {
		return $this->days( 0 );
	}

	/**
	 * Schedule the event to run weekly.
	 *
	 * @return static
	 */
	public function weekly() {
		return $this->spliceIntoPosition( 1, 0 )
					->spliceIntoPosition( 2, 0 )
					->spliceIntoPosition( 5, 0 );
	}

	/**
	 * Schedule the event to run weekly on a given day and time.
	 *
	 * @param  int    $day
	 * @param  string $time
	 * @return static
	 */
	public function weeklyOn( $day, $time = '0:0' ) {
		$this->dailyAt( $time );

		return $this->spliceIntoPosition( 5, $day );
	}

	/**
	 * Schedule the event to run monthly.
	 *
	 * @return static
	 */
	public function monthly() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, 0 )
			->spliceIntoPosition( 3, 1 );
	}

	/**
	 * Schedule the event to run monthly on a given day and time.
	 *
	 * @param  int    $day
	 * @param  string $time
	 * @return static
	 */
	public function monthlyOn( $day = 1, $time = '0:0' ) {
		$this->dailyAt( $time );

		return $this->spliceIntoPosition( 3, $day );
	}

	/**
	 * Schedule the event to run twice monthly at a given time.
	 *
	 * @param  int    $first
	 * @param  int    $second
	 * @param  string $time
	 * @return static
	 */
	public function twiceMonthly( $first = 1, $second = 16, $time = '0:0' ) {
		$days = $first . ',' . $second;

		$this->dailyAt( $time );

		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, 0 )
			->spliceIntoPosition( 3, $days );
	}

	/**
	 * Schedule the event to run on the last day of the month.
	 *
	 * @param  string $time
	 * @return static
	 */
	public function lastDayOfMonth( $time = '0:0' ) {
		$this->dailyAt( $time );

		return $this->spliceIntoPosition( 3, Carbon::now()->endOfMonth()->day );
	}

	/**
	 * Schedule the event to run quarterly.
	 *
	 * @return static
	 */
	public function quarterly() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, 0 )
			->spliceIntoPosition( 3, 1 )
			->spliceIntoPosition( 4, '1-12/3' );
	}

	/**
	 * Schedule the event to run yearly.
	 *
	 * @return static
	 */
	public function yearly() {
		return $this->spliceIntoPosition( 1, 0 )
			->spliceIntoPosition( 2, 0 )
			->spliceIntoPosition( 3, 1 )
			->spliceIntoPosition( 4, 1 );
	}

	/**
	 * Set the days of the week the command should run on.
	 *
	 * @param  array|mixed $days
	 * @return static
	 */
	public function days( $days ) {
		$days = is_array( $days ) ? $days : func_get_args();

		return $this->spliceIntoPosition( 5, implode( ',', $days ) );
	}

	/**
	 * Set the timezone the date should be evaluated on.
	 *
	 * @param  \DateTimeZone|string $timezone
	 * @return static
	 */
	public function timezone( $timezone ) {
		$this->timezone = $timezone;

		return $this;
	}

	/**
	 * Splice the given value into the given position of the expression.
	 *
	 * @param  int        $position
	 * @param  int|string $value
	 * @return static
	 */
	protected function spliceIntoPosition( int $position, int|string $value ) {
		$segments = explode( ' ', $this->expression );

		$segments[ $position - 1 ] = (string) $value;

		return $this->cron( implode( ' ', $segments ) );
	}
}
