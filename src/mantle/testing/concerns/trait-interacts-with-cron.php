<?php
/**
 * Interacts_With_Cron trait file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Testing\Concerns;

use InvalidArgumentException;
use Mantle\Contracts\Queue\Job;
use Mantle\Contracts\Queue\Queue_Manager;
use PHPUnit\Framework\Assert as PHPUnit;
use stdClass;

/**
 * Concern for interacting with the WordPress cron and making assertions against
 * it. Also supports queued and scheduled jobs.
 */
trait Interacts_With_Cron {
	/**
	 * Assert that an action is in the cron queue.
	 *
	 * @param string $action Action hook of the event.
	 * @param array  $args Arguments for the cron queue event.
	 */
	public function assertInCronQueue( string $action, array $args = [] ): void {
		if ( $this->is_job_action( $action ) ) {
			$this->assertJobQueued( $action, $args );
			return;
		}

		PHPUnit::assertNotFalse(
			\wp_next_scheduled( $action, $args ),
			"Cron action is not in cron queue: [$action]"
		);
	}

	/**
	 * Assert tha an action is not in a cron queue.
	 *
	 * @param string $action Action hook of the event.
	 * @param array  $args Arguments for the cron queue event.
	 */
	public function assertNotInCronQueue( string $action, array $args = [] ): void {
		if ( $this->is_job_action( $action ) ) {
			$this->assertJobNotQueued( $action, $args );
			return;
		}

		PHPUnit::assertFalse(
			\wp_next_scheduled( $action, $args ),
			"Cron action is in cron queue: [$action]"
		);
	}

	/**
	 * Determine if a cron 'action' is actually a queued job.
	 *
	 * @param string $action Action name.
	 * @return bool
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
	 * @param string|mixed $job Job class/instance.
	 * @param array        $args Job arguments for class, optional.
	 * @param string       $queue Queue, optional.
	 *
	 * @throws InvalidArgumentException Thrown for missing job class.
	 */
	public function assertJobQueued( $job, array $args = [], string $queue = null ): void {
		$provider = app( Queue_Manager::class )->get_provider();

		if ( is_string( $job ) ) {
			if ( ! class_exists( $job ) ) {
				throw new InvalidArgumentException( "Job class not found: [$job]" );
			}

			$job = new $job( ...$args );
		}

		PHPUnit::assertTrue(
			$provider->in_queue( $job, $queue ),
			'Job is not in the queue.'
		);
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
	 *
	 * @throws InvalidArgumentException Thrown for missing job class.
	 */
	public function assertJobNotQueued( $job, array $args = [], string $queue = null ): void {
		$provider = app( Queue_Manager::class )->get_provider();

		if ( is_string( $job ) ) {
			if ( ! class_exists( $job ) ) {
				throw new InvalidArgumentException( "Job class not found: [$job]" );
			}

			$job = new $job( ...$args );
		}

		PHPUnit::assertFalse(
			$provider->in_queue( $job, $queue ),
			'Job is in the queue.'
		);
	}

	/**
	 * Dispatch the cron.
	 *
	 * @param string $action Optionally run a specific cron action, otherwise run
	 *                       all due tasks.
	 * @return void
	 */
	public function dispatch_cron( string $action = null ) {
		$events = static::get_cron_events();

		if ( empty( $events ) ) {
			return;
		}

		// Check if the action is in the cron events.
		if ( $action ) {
			$hooks = \wp_list_pluck( $events, 'hook' );

			// Bail if the requested action is not found in the schedule.
			if ( ! in_array( $action, $hooks, true ) ) {
				return;
			}
		}

		$due_events = [];
		foreach ( $events as $event ) {
			if ( $action && $event->hook !== $action ) {
				continue;
			}

			if ( time() >= $event->time ) {
				$due_events[] = $event;
			}
		}

		$events = $due_events;

		if ( empty( $events ) ) {
			return;
		}

		array_walk( $events, [ static::class, 'run_cron_event' ] );
	}

	/**
	 * Fetches an array of scheduled cron events.
	 *
	 * @return array
	 */
	protected static function get_cron_events(): array {
		$crons  = _get_cron_array();
		$events = [];

		if ( empty( $crons ) ) {
			return [];
		}

		foreach ( $crons as $time => $hooks ) {
			if ( empty( $hooks ) ) {
				continue;
			}

			foreach ( (array) $hooks as $hook => $hook_events ) {
				foreach ( $hook_events as $sig => $data ) {

					$events[] = (object) [
						'hook'     => $hook,
						'time'     => $time,
						'sig'      => $sig,
						'args'     => $data['args'],
						'schedule' => $data['schedule'],
					];
				}
			}
		}

		return $events;
	}

	/**
	 * Run a cron event.
	 *
	 * @param \stdClass $event Cron event object.
	 */
	protected static function run_cron_event( \stdClass $event ) {
		if ( ! defined( 'DOING_CRON' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Using native WordPress constant.
			define( 'DOING_CRON', true );
		}

		if ( false !== $event->schedule ) {
			$new_args = [ $event->time, $event->schedule, $event->hook, $event->args ];
			call_user_func_array( 'wp_reschedule_event', $new_args );
		}

		\wp_unschedule_event( $event->time, $event->hook, $event->args );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Can't prefix dynamic hooks here, calling registered hooks.
		\do_action_ref_array( $event->hook, $event->args );
	}

	/**
	 * Dispatch the WordPress cron queue.
	 *
	 * @param string $queue Queue to run.
	 */
	public static function dispatch_queue( string $queue = null ) {
		app( \Mantle\Queue\Providers\WordPress\Scheduler::class )->on_queue_run( $queue );
	}
}
