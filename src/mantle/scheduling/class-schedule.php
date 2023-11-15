<?php
/**
 * Schedule class file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 */

namespace Mantle\Scheduling;

use DateTimeZone;
use Mantle\Console\Command;
use Mantle\Contracts\Application;
use Mantle\Contracts\Container;
use Mantle\Contracts\Queue\Job;
use Mantle\Support\Collection;
use RuntimeException;

use function Mantle\Support\Helpers\collect;

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
	 * @var Application
	 */
	protected $container;

	/**
	 * Timezone for scheduling.
	 *
	 * @var DateTimeZone|null
	 */
	protected ?DateTimeZone $timezone = null;

	/**
	 * All of the events on the schedule.
	 *
	 * @var Event[]
	 */
	protected $events = [];

	/**
	 * Constructor.
	 *
	 * @param Application  $container Application container instance.
	 * @param DateTimeZone $timezone Timezone instance, optional.
	 */
	public function __construct( Application $container, DateTimeZone $timezone = null ) {
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
			fn () => app( static::class )->run_due_events(),
		);
	}

	/**
	 * Run the scheduled events that are due to run.
	 */
	public function run_due_events(): void {
		$this
			->due_events( $this->container )
			->each( fn ( Event $event ) => $event->run( $this->container ) );
	}

	/**
	 * Add a new command event.
	 *
	 * @param class-string<\Mantle\Console\Command> $command Command class to run.
	 * @param array                                $arguments Arguments for the command.
	 * @param array                                $assoc_args Assoc. arguments for the command.
	 * @return Event
	 *
	 * @throws RuntimeException Thrown on missing command.
	 * @throws RuntimeException Thrown invalid command class.
	 */
	public function command( string $command, array $arguments = [], array $assoc_args = [] ): Event {
		if ( ! class_exists( $command ) ) {
			throw new RuntimeException( "Command class not found: [{$command}]" );
		}

		if ( ! is_subclass_of( $command, Command::class ) ) {
			throw new RuntimeException( "Invalid command class passed: [{$command}]" );
		}

		$event = new Command_Event( $command, $arguments, $assoc_args, $this->get_timezone() );

		$this->events[] = $event;

		return $event;
	}

	/**
	 * Add a new job event.
	 *
	 * @param class-string $job Job class to run.
	 * @param array        $arguments Arguments for the command.
	 * @return Event
	 *
	 * @throws RuntimeException Thrown on missing command.
	 * @throws RuntimeException Thrown invalid command class..
	 */
	public function job( string $job, array $arguments = [] ): Event {
		if ( ! class_exists( $job ) ) {
			throw new RuntimeException( "Job class not found: [{$job}]" );
		}

		if ( ! is_subclass_of( $job, Job::class ) ) {
			throw new RuntimeException( "Invalid command class passed: [{$job}]" );
		}

		$event = new Job_Event( $job, $arguments, $this->get_timezone() );

		$this->events[] = $event;

		return $event;
	}

	/**
	 * Add a callback event.
	 *
	 * @param callable $callback Callback to run.
	 * @param array    $arguments Arguments for the callback.
	 * @return Event
	 */
	public function call( callable $callback, array $arguments = [] ): Event {
		$event = new Event( $callback, $arguments, $this->get_timezone() );

		$this->events[] = $event;

		return $event;
	}

	/**
	 * Get all of the events on the schedule that are due.
	 *
	 * @param  Application $app Application instance.
	 * @return Collection<int, Event>
	 */
	public function due_events( Application $app ): Collection {
		return collect( $this->events() )->filter(
			fn ( Event $event ) => $event->is_due( $app ),
		);
	}

	/**
	 * Get all of the events on the schedule.
	 *
	 * @return Event[]
	 */
	public function events(): array {
		return $this->events;
	}
}
