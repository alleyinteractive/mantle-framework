<?php
/**
 * Queries_Dates trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Query\Concerns;

use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Logic to query posts by dates.
 *
 * @link https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
 *
 * @mixin \Mantle\Database\Query\Post_Query_Builder
 */
trait Queries_Dates {
	/**
	 * Date constraints to apply to the query.
	 *
	 * @var array<int, array<{date: DateTimeInterface|int|string, compare: string, column: string}>>
	 */
	protected array $date_constraints = [];

	/**
	 * The valid comparison operators for a date query.
	 *
	 * @var array
	 */
	protected array $date_operators = [
		'=',
		'!=',
		'>',
		'>=',
		'<',
		'<=',
	];

	/**
	 * Add a date query for a specific date to the query.
	 *
	 * @param DateTimeInterface|int|string $date
	 * @param string $compare Comparison operator, defaults to '='.
	 * @param string $column Column to compare against, defaults to 'post_date'.
	 * @return static
	 */
	public function whereDate( DateTimeInterface|int|string $date, string $compare = '=', string $column = 'post_date' ): static {
		if ( ! in_array( $compare, $this->date_operators, true ) ) {
			throw new InvalidArgumentException( 'Invalid date comparison operator: ' . $compare );
		}

		$this->date_constraints[] = compact( 'date', 'compare', 'column' );

		return $this;
	}

	// public function whereDateBetween(
	// 	DateTimeInterface|int|string $start,
	// 	DateTimeInterface|int|string $end,
	// 	string $column = 'post_date'
	// ): static {
	// 	$this->date_constraints[] = [
	// 		'date1'  => $date1,
	// 		'date2'  => $date2,
	// 		'column' => $column,
	// 	];

	// 	return $this;
	// }

	/**
	 * Query for objects older than the given date.
	 *
	 * @param DateTimeInterface|int $date
	 * @return static
	 */
	public function olderThan( DateTimeInterface|int $date ): static {
		return $this->whereDate( $date, '<' );
	}

	/**
	 * Query for objects older than or equal to the given date.
	 *
	 * @param DateTimeInterface|int $date
	 * @return static
	 */
	public function olderThanOrEqualTo( DateTimeInterface|int $date ): static {
		return $this->whereDate( $date, '<=' );
	}

	/**
	 * Alias for olderThan().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string $column Column to compare against.
	 * @return static
	 */
	public function older_than( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->olderThan( $date, $column );
	}

	/**
	 * Alias for olderThanOrEqualTo().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string $column Column to compare against.
	 * @return static
	 */
	public function older_than_or_equal_to( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '<=', $column );
	}

	/**
	 * Query for objects newer than the given date.
	 *
	 * @param DateTimeInterface|int $date
	 * @param string $column Column to compare against.
	 * @return static
	 */
	public function newerThan( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '>', $column );
	}

	/**
	 * Query for objects newer than or equal to the given date.
	 *
	 * @param DateTimeInterface|int $date
	 * @param string $column Column to compare against.
	 * @return static
	 */
	public function newerThanOrEqualTo( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '>=', $column );
	}

	/**
	 * Alias for newerThan().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string $column Column to compare against.
	 * @return static
	 */
	public function newer_than( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->newerThan( $date, $column );
	}

	/**
	 * Alias for newerThanOrEqualTo().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string $column Column to compare against.
	 * @return static
	 */
	public function newer_than_or_equal_to( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->newerThanOrEqualTo( $date, $column );
	}

	/**
	 * Calculate the arguments for the date query to pass to either WP_Query.
	 *
	 * @return array
	 */
	protected function get_date_query_args(): array {
		if ( empty( $this->date_constraints ) ) {
			return [];
		}

		$date_query = [];

		foreach ( $this->date_constraints as $constraint ) {
			$date = $constraint['date'];

			if ( is_int( $date ) ) {
				$date = Carbon::createFromTimestamp( $date, wp_timezone() );
			} elseif ( is_string( $date ) ) {
				$date = Carbon::parse( $date, wp_timezone() );
			} elseif ( $date instanceof DateTimeInterface ) {
				$date = Carbon::instance( $date );
			}

			switch ( $constraint['compare'] ) {
				case '<':
					$date_query[] = [
						'column' => $constraint['column'],
						'before' => $date->toDateTimeString(),
					];
					break;

				case '<=':
					$date_query[] = [
						'column'    => $constraint['column'],
						'before'    => $date->toDateTimeString(),
						'inclusive' => true,
					];
					break;

				case '>':
					$date_query[] = [
						'column' => $constraint['column'],
						'after'  => $date->toDateTimeString(),
					];
					break;

				case '>=':
					$date_query[] = [
						'column'    => $constraint['column'],
						'after'     => $constraint['date']->toDateTimeString(),
						'inclusive' => true,
					];
					break;

				case '=':
				case '!=':
					$date_query[] = [
						'compare'             => $constraint['compare'],
						// $constraint['column'] => $date->toDateTimeString(),
						// 'post_d'
						'column' => $constraint['column'],
						'year'   => $date->format( 'Y' ),
						'month'  => $date->format( 'm' ),
						'day'    => $date->format( 'd' ),
						'hour'   => $date->format( 'H' ),
						'minute' => $date->format( 'i' ),
						'second' => $date->format( 's' ),
					];
					break;
			}
		}

		return [
			'date_query' => $date_query,
		];

		$query = new \WP_Date_Query( $date_query );
		dd( $query->get_sql() );

		dd( $this->date_constraints );
	}
}
