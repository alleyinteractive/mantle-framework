<?php
/**
 * Queue_Fake class file
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Queue\Queue_Manager;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Queue Fake
 *
 * Used as a fake provider to perform assertions against.
 */
class Queue_Fake extends Queue_Manager {
	/**
	 * Assert if a job was pushed based on a truth-test callback.
	 *
	 * @param  string|\Closure   $job
	 * @param  callable|int|null $callback
	 * @return void
	 */
	public function assertPushed( $job, $callback = null ) {
		if ( is_numeric( $callback ) ) {
			$this->assertPushedTimes( $job, $callback );
			return;
		}

		PHPUnit::assertTrue(
			$this->pushed( $job, $callback )->count() > 0,
			"The expected [{$job}] job was not pushed."
		);
	}

	/**
	 * Assert if a job was pushed a number of times.
	 *
	 * @param  string $job
	 * @param  int    $times
	 * @return void
	 */
	protected function assertPushedTimes( $job, $times = 1 ) {
		$count = $this->pushed( $job )->count();

		PHPUnit::assertSame(
			$times,
			$count,
			"The expected [{$job}] job was pushed {$count} times instead of {$times} times."
		);
	}

	/**
	 * Determine if a job was pushed based on a truth-test callback.
	 *
	 * @param  string|\Closure $job
	 * @param  callable|null   $callback
	 * @return void
	 */
	public function assertNotPushed( $job, $callback = null ) {
			PHPUnit::assertCount(
				0,
				$this->pushed( $job, $callback ),
				"The unexpected [{$job}] job was pushed."
			);
	}

	/**
	 * Assert that no jobs were pushed.
	 *
	 * @return void
	 */
	public function assertNothingPushed() {
		PHPUnit::assertEmpty( $this->jobs, 'Jobs were pushed unexpectedly.' );
	}

	/**
	 * Get all of the jobs matching a truth-test callback.
	 *
	 * @param  string        $job
	 * @param  callable|null $callback
	 * @return \Illuminate\Support\Collection
	 */
	public function pushed( $job, $callback = null ) {
		if ( ! $this->hasPushed( $job ) ) {
			return collect();
		}

		$callback = $callback ?: function () {
			return true;
		};

		return collect( $this->jobs[ $job ] )->filter(
			function ( $data ) use ( $callback ) {
				return $callback( $data['job'], $data['queue'] );
			}
		)->pluck( 'job' );
	}

	/**
	 * Determine if there are any stored jobs for a given class.
	 *
	 * @param  string $job
	 * @return bool
	 */
	public function hasPushed( $job ) {
		return isset( $this->jobs[ $job ] ) && ! empty( $this->jobs[ $job ] );
	}
}
