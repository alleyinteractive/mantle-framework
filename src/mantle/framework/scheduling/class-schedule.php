<?php
/**
 * Schedule class file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 */

namespace Mantle\Framework\Scheduling;

use DateTimeZone;
use Mantle\Framework\Console\Command;
use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Contracts\Container;
use Mantle\Framework\Contracts\Queue\Job;
use Mantle\Support\Collection;
use RuntimeException;

use function Mantle\Framework\Helpers\collect;

/**
 * Event Scheduler
 */
class Schedule {
	/**
	 * WordPress cron hook for the scheduler.
	 *
	 * @var string
	 */
	public const CRON_HOOK = 'mantle_scheduled_event';

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Timezone for scheduling.
	 *
	 * @var DateTimeZone
	 */
	protected $timezone;

	/**
	 * All of the events on the schedule.
	 *
	 * @var Event[]
	 */
	protected $events = [];

	/**
	 * Constructor.
	 *
	 * @param Container    $container Container instance.
	 * @param DateTimeZone $timezone Timezone instance, optional.
	 */
	public function __construct( Container $container, DateTimeZone $timezone = null ) {
		$this->container = $container;

		if ( $timezone ) {
			$this->timezone = $timezone;
		}
	}

	/**
	 * Get the timezone instance for scheduling.
	 *
	 * @return DateTimeZone
	 */
	protected function get_timezone(): DateTimeZone {
		return $this->timezone ?? \wp_timezone();
	}

	/**
	 * Schedule the WordPress cron event for the scheduler.
	 */
	public static function schedule_cron_event() {
		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! wp_next_scheduled( static::CRON_HOOK ) ) {
			\wp_schedule_single_event( time() + MINUTE_IN_SECONDS, static::CRON_HOOK );
		}

		\add_action(
			static::CRON_HOOK,
			function() {
				app( static::class )->run_due_events();
			}
		);
	}

	/**
	 * Run the scheduled events that are due to run.
	 */
	public function run_due_events() {
		$this
			->due_events( $this->container )
			->each(
				function ( Event $event ) {
					$event->run( $this->container );
				}
			);
	}

	/**
	 * Add a new command event.
	 *
	 * @param string $command Command class to run.
	 * @param array  $arguments Arguments for the command.
	 * @param array  $assoc_args Assoc. arguments for the command.
	 * @return Event
	 *
	 * @throws RuntimeException Thrown on missing command.
	 * @throws RuntimeException Thrown invalid command class.
	 */
	public function command( string $command, array $arguments = [], array $assoc_args = [] ): Event {
		if ( ! class_exists( $command ) ) {
			throw new RuntimeException( "Command class not found: [${command}]" );
		}

		if ( ! is_subclass_of( $command, Command::class ) ) {
			throw new RuntimeException( "Invalid command class passed: [${command}]" );
		}

		$event = new Command_Event( $command, $arguments, $assoc_args, $this->get_timezone() );

		$this->events[] = $event;

		return $event;
	}

	/**
	 * Add a new job event.
	 *
	 * @param string $job Job class to run.
	 * @param array  $arguments Arguments for the command.
	 * @return Event
	 *
	 * @throws RuntimeException Thrown on missing command.
	 * @throws RuntimeException Thrown invalid command class..
	 */
	public function job( string $job, array $arguments = [] ): Event {
		if ( ! class_exists( $job ) ) {
			throw new RuntimeException( "Job class not found: [${job}]" );
		}

		if ( ! is_subclass_of( $job, Job::class ) ) {
			throw new RuntimeException( "Invalid command class passed: [${job}]" );
		}

		$event = new Job_Event( $job, $arguments, $this->get_timezone() );

		$this->events[] = $event;

		return $event;
	}

	/**
	 * Add a callback event.
	 *
	 * @param string $callback Callback to run.
	 * @param array  $arguments Arguments for the callback.
	 * @return Event
	 */
	public function call( $callback, array $arguments = [] ): Event {
		$event = new Event( $callback, $arguments, $this->get_timezone() );

		$this->events[] = $event;

		return $event;
	}

	/**
	 * Get all of the events on the schedule that are due.
	 *
	 * @param  Application $app Application instance.
	 * @return Collection
	 */
	public function due_events( Application $app ): Collection {
		return collect( $this->events )->filter->is_due( $app );
	}

	/**
	 * Get all of the events on the schedule.
	 *
	 * @return Event[]
	 */
	public function events() {
		return $this->events;
	}
}
