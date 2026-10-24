<?php
/**
 * Manages_Frequencies trait file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Scheduling;

use Carbon\Carbon;

/**
 * Manages Frequency logic for scheduled events.
 *
 * Provides a very fluent interface to schedule a command to be run.
 *
 * @mixin \Mantle\Scheduling\Event
 */
trait Manages_Frequencies {
	/**
	 * The Cron expression representing the event's frequency.
	 *
	 * @param  string $expression
	 */
	public function cron( string $expression ): static {
		$this->expression = $expression;

		return $this;
	}

	/**
	 * Schedule the event to run between start and end time.
	 *
	 * @param  string $start_time
	 * @param  string $end_time
	 */
	public function between( string $start_time, string $end_time ): static {
		return $this->when( $this->inTimeInterval( $start_time, $end_time ) );
	}

	/**
	 * Schedule the event to not run between start and end time.
	 *
	 * @param  string $start_time
	 * @param  string $end_time
	 */
	public function unlessBetween( string $start_time, string $end_time ): static {
		return $this->skip( $this->inTimeInterval( $start_time, $end_time ) );
	}

	/**
	 * Schedule the event to run between start and end time.
	 *
	 * @param  string $start_time
	 * @param  string $end_time
	 */
	private function inTimeInterval( string $start_time, string $end_time ): \Closure {
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
	 */
	public function everyMinute(): static {
		return $this->splice_into_position( 1, '*' );
	}

	/**
	 * Schedule the event to run every two minutes.
	 */
	public function everyTwoMinutes(): static {
		return $this->splice_into_position( 1, '*/2' );
	}

	/**
	 * Schedule the event to run every three minutes.
	 */
	public function everyThreeMinutes(): static {
		return $this->splice_into_position( 1, '*/3' );
	}

	/**
	 * Schedule the event to run every four minutes.
	 */
	public function everyFourMinutes(): static {
		return $this->splice_into_position( 1, '*/4' );
	}

	/**
	 * Schedule the event to run every five minutes.
	 */
	public function everyFiveMinutes(): static {
		return $this->splice_into_position( 1, '*/5' );
	}

	/**
	 * Schedule the event to run every ten minutes.
	 */
	public function everyTenMinutes(): static {
		return $this->splice_into_position( 1, '*/10' );
	}

	/**
	 * Schedule the event to run every fifteen minutes.
	 */
	public function everyFifteenMinutes(): static {
		return $this->splice_into_position( 1, '*/15' );
	}

	/**
	 * Schedule the event to run every thirty minutes.
	 */
	public function everyThirtyMinutes(): static {
		return $this->splice_into_position( 1, '0,30' );
	}

	/**
	 * Schedule the event to run hourly.
	 */
	public function hourly(): static {
		return $this->splice_into_position( 1, 0 );
	}

	/**
	 * Schedule the event to run hourly at a given offset in the hour.
	 *
	 * @param  int[]|int $offset
	 */
	public function hourlyAt( $offset ): static {
		$offset = is_array( $offset ) ? implode( ',', $offset ) : $offset;

		return $this->splice_into_position( 1, $offset );
	}

	/**
	 * Schedule the event to run every two hours.
	 */
	public function everyTwoHours(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, '*/2' );
	}

	/**
	 * Schedule the event to run every three hours.
	 */
	public function everyThreeHours(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, '*/3' );
	}

	/**
	 * Schedule the event to run every four hours.
	 */
	public function everyFourHours(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, '*/4' );
	}

	/**
	 * Schedule the event to run every six hours.
	 */
	public function everySixHours(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, '*/6' );
	}

	/**
	 * Schedule the event to run daily.
	 */
	public function daily(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, 0 );
	}

	/**
	 * Schedule the command at a given time.
	 *
	 * @param  string $time
	 */
	public function at( $time ): static {
		return $this->dailyAt( $time );
	}

	/**
	 * Schedule the event to run daily at a given time (10:00, 19:30, etc).
	 *
	 * @param  string $time
	 */
	public function dailyAt( $time ): static {
		$segments = explode( ':', $time );

		return $this->splice_into_position( 2, (int) $segments[0] )
			->splice_into_position( 1, 2 === count( $segments ) ? (int) $segments[1] : '0' );
	}

	/**
	 * Schedule the event to run twice daily.
	 *
	 * @param  int $first
	 * @param  int $second
	 */
	public function twiceDaily( $first = 1, $second = 13 ): static {
		$hours = $first . ',' . $second;

		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, $hours );
	}

	/**
	 * Schedule the event to run only on weekdays.
	 */
	public function weekdays(): static {
		return $this->splice_into_position( 5, '1-5' );
	}

	/**
	 * Schedule the event to run only on weekends.
	 */
	public function weekends(): static {
		return $this->splice_into_position( 5, '0,6' );
	}

	/**
	 * Schedule the event to run only on Mondays.
	 */
	public function mondays(): static {
		return $this->days( 1 );
	}

	/**
	 * Schedule the event to run only on Tuesdays.
	 */
	public function tuesdays(): static {
		return $this->days( 2 );
	}

	/**
	 * Schedule the event to run only on Wednesdays.
	 */
	public function wednesdays(): static {
		return $this->days( 3 );
	}

	/**
	 * Schedule the event to run only on Thursdays.
	 */
	public function thursdays(): static {
		return $this->days( 4 );
	}

	/**
	 * Schedule the event to run only on Fridays.
	 */
	public function fridays(): static {
		return $this->days( 5 );
	}

	/**
	 * Schedule the event to run only on Saturdays.
	 */
	public function saturdays(): static {
		return $this->days( 6 );
	}

	/**
	 * Schedule the event to run only on Sundays.
	 */
	public function sundays(): static {
		return $this->days( 0 );
	}

	/**
	 * Schedule the event to run weekly.
	 */
	public function weekly(): static {
		return $this->splice_into_position( 1, 0 )
					->splice_into_position( 2, 0 )
					->splice_into_position( 5, 0 );
	}

	/**
	 * Schedule the event to run weekly on a given day and time.
	 *
	 * @param  int    $day
	 * @param  string $time
	 */
	public function weeklyOn( int $day, string $time = '0:0' ): static {
		$this->dailyAt( $time );

		return $this->splice_into_position( 5, $day );
	}

	/**
	 * Schedule the event to run monthly.
	 */
	public function monthly(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, 0 )
			->splice_into_position( 3, 1 );
	}

	/**
	 * Schedule the event to run monthly on a given day and time.
	 *
	 * @param  int    $day
	 * @param  string $time
	 */
	public function monthlyOn( int $day = 1, string $time = '0:0' ): static {
		$this->dailyAt( $time );

		return $this->splice_into_position( 3, $day );
	}

	/**
	 * Schedule the event to run twice monthly at a given time.
	 *
	 * @param  int    $first
	 * @param  int    $second
	 * @param  string $time
	 */
	public function twiceMonthly( int $first = 1, int $second = 16, string $time = '0:0' ): static {
		$days = $first . ',' . $second;

		$this->dailyAt( $time );

		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, 0 )
			->splice_into_position( 3, $days );
	}

	/**
	 * Schedule the event to run on the last day of the month.
	 *
	 * @param  string $time
	 */
	public function lastDayOfMonth( string $time = '0:0' ): static {
		$this->dailyAt( $time );

		return $this->splice_into_position( 3, Carbon::now()->endOfMonth()->day );
	}

	/**
	 * Schedule the event to run quarterly.
	 */
	public function quarterly(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, 0 )
			->splice_into_position( 3, 1 )
			->splice_into_position( 4, '1-12/3' );
	}

	/**
	 * Schedule the event to run yearly.
	 */
	public function yearly(): static {
		return $this->splice_into_position( 1, 0 )
			->splice_into_position( 2, 0 )
			->splice_into_position( 3, 1 )
			->splice_into_position( 4, 1 );
	}

	/**
	 * Set the days of the week the command should run on.
	 *
	 * @param  array<int>|int $days
	 */
	public function days( array|int $days ): static {
		$days = is_array( $days ) ? $days : func_get_args();

		return $this->splice_into_position( 5, implode( ',', $days ) );
	}

	/**
	 * Set the timezone the date should be evaluated on.
	 *
	 * @param  \DateTimeZone|string $timezone
	 */
	public function timezone( \DateTimeZone|string $timezone ): static {
		$this->timezone = is_string( $timezone ) ? new \DateTimeZone( $timezone ) : $timezone;

		return $this;
	}

	/**
	 * Splice the given value into the given position of the expression.
	 *
	 * @param  int        $position
	 * @param  int|string $value
	 */
	protected function splice_into_position( int $position, int|string $value ): static {
		$segments = explode( ' ', $this->expression );

		$segments[ $position - 1 ] = (string) $value;

		return $this->cron( implode( ' ', $segments ) );
	}
}
