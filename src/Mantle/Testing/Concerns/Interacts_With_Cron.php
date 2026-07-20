<?php
/**
 * Interacts_With_Cron trait file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

use InvalidArgumentException;
use Mantle\Contracts\Queue\Job;
use Mantle\Contracts\Queue\Provider;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Queue\Worker;
use Mantle\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;

/**
 * Concern for interacting with the WordPress cron and making assertions against
 * it. Also supports queued jobs via Mantle's queue system.
 *
 * @phpstan-type CronEvent object{
 *   hook: non-empty-string,
 *   time: int,
 *   sig: string,
 *   args: list<mixed>,
 *   schedule: false|string
 * }
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Interacts_With_Cron {
	/**
	 * Assert that an action is in the cron queue.
	 *
	 * @param string       $action Action hook of the event.
	 * @param array<mixed> $args Arguments for the cron queue event or null to not
	 *                           check arguments (cron only).
	 * @param string|null  $queue Queue name, optional. Only applies to queue jobs not cron events.
	 */
	public function assertInCronQueue( string $action, array|null $args = null, ?string $queue = null ): void {
		if ( class_exists( $action ) && $this->is_job_action( $action ) ) {
			$this->assertJobQueued( $action, is_array( $args ) ? $args : [], $queue );
			return;
		}

		if ( ! is_null( $args ) ) {
			PHPUnit::assertNotFalse(
				\wp_next_scheduled( $action, $args ), // @phpstan-ignore-line argument.type
				"Cron action not scheduled: [{$action}] (comparing arguments)"
			);
		} else {
			PHPUnit::assertNotEmpty(
				static::get_cron_events()->where( 'hook', $action )->all(),
				"Cron action not scheduled: [{$action}] (ignoring checked)",
			);
		}
	}

	/**
	 * Alias for `assertInCronQueue`.
	 *
	 * @param string       $action Action hook of the event.
	 * @param array<mixed> $args Arguments for the cron queue event or null to not
	 *                           check arguments (cron only).
	 * @param string|null  $queue Queue name, optional.
	 */
	public function assertCronScheduled( string $action, array|null $args = null, ?string $queue = null ): void {
		$this->assertInCronQueue( $action, $args, $queue );
	}

	/**
	 * Assert that an action is not in a cron queue.
	 *
	 * With Mantle 1.9, the default of $args was changed to `null`. This function
	 * would now return true if no argument was passed to `$args` and the cron
	 * hook was scheduled with any argument.
	 *
	 * @param string       $action Action hook of the event.
	 * @param array<mixed> $args Arguments for the cron queue event or null to not check
	 *                    arguments (cron only).
	 */
	public function assertNotInCronQueue( string $action, array|null $args = null ): void {
		if ( class_exists( $action ) && $this->is_job_action( $action ) ) {
			$this->assertJobNotQueued( $action, is_array( $args ) ? $args : [] );
			return;
		}

		if ( ! is_null( $args ) ) {
			PHPUnit::assertFalse(
				\wp_next_scheduled( $action, $args ), // @phpstan-ignore-line argument.type
				"Cron action scheduled: [{$action}] (comparing arguments)"
			);
		} else {
			PHPUnit::assertEmpty(
				static::get_cron_events()->where( 'hook', $action )->all(),
				"Cron action scheduled: [{$action}] (ignoring checked)",
			);
		}
	}

	/**
	 * Alias for `assertNotInCronQueue`.
	 *
	 * @param string       $action Action hook of the event.
	 * @param array<mixed> $args Arguments for the cron queue event or null to not check.
	 */
	public function assertCronNotScheduled( string $action, array|null $args = null ): void {
		$this->assertNotInCronQueue( $action, $args );
	}

	/**
	 * Determine if a cron 'action' is actually a queued job.
	 *
	 * @param class-string|class-string<\Mantle\Contracts\Queue\Job> $action Action name.
	 */
	protected function is_job_action( string $action ): bool {
		return class_exists( $action ) && in_array( Job::class, class_implements( $action ), true );
	}

	/**
	 * Assert if a job has been queued.
	 *
	 * Supports passing a job instance as a class or as a string (class name) with arguments
	 * in the second function argument.
	 *
	 * This is not the WordPress cron, only the queue system.
	 *
	 * @param string|mixed $job Job class/instance.
	 * @param array        $args Job arguments for class, optional.
	 * @param string       $queue Queue, optional.
	 * @param int|null     $count Expected count of jobs in the queue, optional.
	 * @param Provider     $provider Provider, optional.
	 *
	 * @throws InvalidArgumentException Thrown for missing job class.
	 */
	public function assertJobQueued( mixed $job, array $args = [], ?string $queue = null, ?int $count = null, ?Provider $provider = null ): void {
		if ( ! $provider instanceof Provider ) {
			$provider = app( Queue_Manager::class )->get_provider();
		}

		assert( $provider instanceof Provider );

		if ( is_string( $job ) ) {
			if ( ! class_exists( $job ) ) {
				throw new InvalidArgumentException( "Job class not found: [{$job}]" );
			}

			$job = new $job( ...$args );
		}

		$job_name = get_debug_type( $job );

		$queue ??= 'default';

		if ( is_null( $count ) ) {
			PHPUnit::assertTrue(
				$provider->in_queue( $job, $queue ),
				"Job [{$job_name}] is not in the queue [{$queue}] for " . $provider::class,
			);
		} else {
			PHPUnit::assertEquals(
				$count,
				$provider->pending_size( $queue ),
				"Job [{$job_name}] count in the queue [{$queue}] is not as expected for " . $provider::class,
			);
		}
	}

	/**
	 * Assert that a job has not been queued.
	 *
	 * Supports passing a job instance as a class or as a string (class name) with arguments
	 * in the second function argument.
	 *
	 * @param string|mixed $job Job class/instance.
	 * @param array        $args Job arguments for class, optional.
	 * @param string       $queue Queue, optional.
	 * @param Provider     $provider Provider, optional.
	 *
	 * @throws InvalidArgumentException Thrown for missing job class.
	 */
	public function assertJobNotQueued( $job, array $args = [], ?string $queue = null, ?Provider $provider = null ): void {
		if ( ! $provider instanceof Provider ) {
			$provider = app( Queue_Manager::class )->get_provider();
		}

		assert( $provider instanceof Provider );

		if ( is_string( $job ) ) {
			if ( ! class_exists( $job ) ) {
				throw new InvalidArgumentException( "Job class not found: [{$job}]" );
			}

			$job = new $job( ...$args );
		}

		$job_name = is_object( $job ) ? $job::class : $job;

		PHPUnit::assertFalse(
			$provider->in_queue( $job, $queue ),
			"Job [{$job_name}] is in the queue.",
		);
	}

	/**
	 * Assert the count of the events in the cron queue.
	 *
	 * Supports passing a hook name to compare against the number of cron events
	 * scheduled against that cron hook. Does not support queue jobs.
	 *
	 * @param string|class-string $action Cron hook name.
	 * @param int                 $count Expected count of cron events.
	 */
	public function assertCronCount( string $action, int $count ): void {
		PHPUnit::assertEquals(
			$count,
			static::get_cron_events()->where( 'hook', $action )->count(),
			"Cron action count is not as expected: [{$action}]",
		);
	}

	/**
	 * Dispatch the cron.
	 *
	 * @param string $action Optionally run a specific cron action, otherwise run
	 *                       all due tasks.
	 * @param bool   $fail_empty Fail if no cron events are found.
	 * @param bool   $future Allow future events to be dispatched.
	 */
	public function dispatch_cron( ?string $action = null, bool $fail_empty = false, bool $future = false ): int {
		$events = static::get_cron_events();

		if ( $events->is_empty() ) {
			if ( $fail_empty ) {
				PHPUnit::fail( 'No cron events found to dispatch.' );
			}

			return 0;
		}

		if ( $action ) {
			$events = $events->where( 'hook', $action );
		}

		// If future events are not allowed, filter out future events.
		if ( ! $future ) {
			$events = $events->filter(
				fn ( object $event ) => time() >= $event->time
			);
		}

		$events = $events->values();

		if ( $events->is_empty() ) {
			if ( $fail_empty ) {
				PHPUnit::fail( 'No due cron events found to dispatch.' );
			}

			return 0;
		}

		$events->each( fn ( object $event ) => self::run_cron_event( $event ) );

		return $events->count();
	}

	/**
	 * Fetches an array of scheduled cron events.
	 *
	 * @return Collection<int, CronEvent>
	 */
	protected static function get_cron_events(): Collection {
		$crons  = _get_cron_array();
		$events = [];

		if ( empty( $crons ) ) {
			return new Collection();
		}

		foreach ( $crons as $time => $hooks ) {
			if ( empty( $hooks ) ) {
				continue;
			}

			foreach ( (array) $hooks as $hook => $hook_events ) {
				foreach ( $hook_events as $sig => $data ) {
					$events[] = (object) [
						'hook'     => (string) $hook,
						'time'     => (int) $time,
						'sig'      => (string) $sig,
						'args'     => array_values( (array) $data['args'] ),
						'schedule' => is_string( $data['schedule'] ) ? $data['schedule'] : false,
					];
				}
			}
		}

		return new Collection( $events ); // @phpstan-ignore-line return.type
	}

	/**
	 * Run a cron event.
	 *
	 * @throws \RuntimeException If the event could not be unscheduled.
	 *
	 * @param object $event Cron event object.
	 * @phpstan-param CronEvent $event
	 */
	private static function run_cron_event( object $event ): void {
		if ( ! defined( 'DOING_CRON' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Using native WordPress constant.
			define( 'DOING_CRON', true );
		}

		if ( false !== $event->schedule ) {
			$new_args = [ $event->time, $event->schedule, $event->hook, $event->args ];
			wp_reschedule_event( ...$new_args );
		}

		$result = \wp_unschedule_event( $event->time, $event->hook, $event->args, true );

		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( sprintf(
				'Failed to unschedule cron event: %s',
				$result->get_error_message()
			) );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Can't prefix dynamic hooks here, calling registered hooks.
		\do_action_ref_array( $event->hook, $event->args );
	}

	/**
	 * Dispatch the WordPress cron queue.
	 *
	 * @throws \RuntimeException If the application container is not available.
	 *
	 * @param int    $size Size of the queue to run.
	 * @param string $queue Queue to run.
	 */
	public function dispatch_queue( int $size = 100, ?string $queue = null ): void {
		if ( ! $this->app ) {
			throw new RuntimeException( 'The application container is not available.' );
		}

		$this->app->make( Worker::class )->run( $size, $queue );
	}
}
