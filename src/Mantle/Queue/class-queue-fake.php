<?php
/**
 * Queue_Fake class file
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Queue\Queue_Manager;
use PHPUnit\Framework\Assert as PHPUnit;

use function Mantle\Support\Helpers\collect;

/**
 * Queue Fake
 *
 * Used as a fake provider to perform assertions against.
 */
class Queue_Fake extends Queue_Manager {
	/**
	 * Pushed jobs.
	 *
	 * @var array
	 */
	protected $jobs = [];

	/**
	 * Assert if a job was pushed based on a truth-test callback.
	 *
	 * @param  string|\Closure   $job
	 * @param  callable|int|null $callback
	 */
	public function assertPushed( $job, $callback = null ): void {
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
	 */
	public function assertNotPushed( $job, $callback = null ): void {
			PHPUnit::assertCount(
				0,
				$this->pushed( $job, $callback ),
				"The unexpected [{$job}] job was pushed."
			);
	}

	/**
	 * Assert that no jobs were pushed.
	 */
	public function assertNothingPushed(): void {
		PHPUnit::assertEmpty( $this->jobs, 'Jobs were pushed unexpectedly.' );
	}

	/**
	 * Get all of the jobs matching a truth-test callback.
	 *
	 * @param  string        $job
	 * @param  callable|null $callback
	 * @return \Mantle\Support\Collection
	 */
	public function pushed( $job, $callback = null ) {
		if ( ! $this->hasPushed( $job ) ) {
			return collect();
		}

		$callback = $callback ?: fn () => true;

		return collect( $this->jobs[ $job ] )->filter(
			fn ( $data ) => $callback( $data['job'], $data['queue'] ),
		)->pluck( 'job' );
	}

	/**
	 * Determine if there are any stored jobs for a given class.
	 *
	 * @param  string $job
	 */
	public function hasPushed( $job ): bool {
		return isset( $this->jobs[ $job ] ) && ! empty( $this->jobs[ $job ] );
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string|object $job
	 * @param  mixed         $data
	 * @param  string        $queue
	 */
	public function push( $job, $data = '', $queue = null ): void {
		$this->jobs[ is_object( $job ) ? $job::class : $job ][] = [
			'data'  => $data,
			'job'   => $job,
			'queue' => $queue,
		];
	}
}
