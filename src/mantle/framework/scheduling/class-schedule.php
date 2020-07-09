<?php
namespace Mantle\Framework\Scheduling;

use DateTimeZone;
use Mantle\Framework\Container\Container;

class Schedule {
	/**
	 * Timezone for scheduling.
	 *
	 * @var DateTimeZone
	 */
	protected $timezone;

	/**
	 * Constructor.
	 *
	 * @param DateTimeZone $timezone Timezone instance, optional.
	 */
	public function __construct( DateTimeZone $timezone = null ) {
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
	 * Add a new command event to the schedule.
	 *
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return \Illuminate\Console\Scheduling\Event
	 */
	public function command($command, array $parameters = [])
	{
			if (class_exists($command)) {
					$command = Container::getInstance()->make( $command )->get_name();
			}

			return $this->exec(
					Application::formatCommandString($command), $parameters
			);
	}

	/**
	 * Add a new job callback event to the schedule.
	 *
	 * @param  object|string  $job
	 * @param  string|null  $queue
	 * @param  string|null  $connection
	 * @return \Illuminate\Console\Scheduling\CallbackEvent
	 */
	public function job($job, $queue = null, $connection = null)
	{
			return $this->call(function () use ($job, $queue, $connection) {
					$job = is_string($job) ? Container::getInstance()->make($job) : $job;

					if ($job instanceof ShouldQueue) {
							$this->dispatchToQueue($job, $queue ?? $job->queue, $connection ?? $job->connection);
					} else {
							$this->dispatchNow($job);
					}
			})->name(is_string($job) ? $job : get_class($job));
	}

	/**
	 * Add a new command event to the schedule.
	 *
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return \Illuminate\Console\Scheduling\Event
	 */
	public function exec($command, array $parameters = [])
	{
			if (count($parameters)) {
					$command .= ' '.$this->compileParameters($parameters);
			}

			$this->events[] = $event = new Event($this->eventMutex, $command, $this->timezone);

			return $event;
	}
}
