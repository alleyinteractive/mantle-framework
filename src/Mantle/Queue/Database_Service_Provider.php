<?php
/**
 * Service_Provider class file
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Queue\Console\Cleanup_Jobs_Command;
use Mantle\Queue\Events;
use Mantle\Scheduling\Schedule;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Service_Provider as Base_Service_Provider;

/**
 * WordPress Queue Service Provider Scheduler
 */
class Database_Service_Provider extends Base_Service_Provider {
	use Concerns\Database_Queue_Schema;

	/**
	 * The queue scheduler instance.
	 */
	protected Database_Scheduler $scheduler;

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->app->singleton( Database_Scheduler::class, fn ( $app ) => new Database_Scheduler(
			$app->make( 'queue' ),
			$app->make( 'queue.worker' ),
			$app->make( 'config' ),
		) );
	}

	/**
	 * Register the WordPress queue provider's post type and taxonomies.
	 *
	 * Registers the cleanup command with the application task scheduler to run
	 * daily (by default) to remove old queue jobs from the database.
	 */
	public function boot(): void {
		$this->scheduler = $this->app->make( Database_Scheduler::class );

		$this->create_tables();

		$this->app->resolving(
			'scheduler',
			fn ( Schedule $scheduler ) => $scheduler->command( Cleanup_Jobs_Command::class )->cron(
				/**
				 * Filter the schedule for the queue cleanup job.
				 *
				 * @param string $schedule Schedule cron expression. Defaults to daily at midnight.
				 */
				(string) apply_filters( 'mantle_queue_cleanup_schedule', '0 0 * * *' ),
			)
		);
	}

	/**
	 * Process the pending queue items that were added before `init`.
	 */
	#[Action( 'init' )]
	public function process_pending_queue(): void {
		Database_Queue_Provider::process_pending_queue();
	}

	/**
	 * Listen for the providers registered event to register the WordPress queue
	 * provider.
	 *
	 * @param Events\Providers_Registered $event Event instance.
	 */
	#[Action( Events\Providers_Registered::class )]
	public function register_queue_provider( Events\Providers_Registered $event ): Events\Providers_Registered {
		$event->manager->add_provider( Database_Queue_Provider::NAME, Database_Queue_Provider::class );

		return $event;
	}

	/**
	 * Handle the schedule event to run the queue via WordPress cron.
	 *
	 * This is the listener for the cron event that will start the process of
	 * firing off queue jobs.
	 *
	 * @param string|null $queue Queue name.
	 */
	#[Action( Database_Scheduler::EVENT )]
	public function handle_scheduled_run( ?string $queue = null ): void {
		$this->scheduler->run( $queue ?? 'default' );
	}

	/**
	 * Handle the Job Queued event to schedule the next cron run.
	 *
	 * @param Events\Job_Queued $event Job Queued event.
	 */
	#[Action( Events\Job_Queued::class )]
	public function handle_job_queued_event( Events\Job_Queued $event ): Events\Job_Queued {
		if ( $event->provider instanceof Database_Queue_Provider ) {
			$this->scheduler->handle_job_queued( $event->queue ?? 'default' );
		}

		return $event;
	}

	/**
	 * Handle the Run Complete event to schedule the next cron run.
	 *
	 * @param Events\Run_Complete $event Run complete event.
	 */
	#[Action( Events\Run_Complete::class )]
	public function handle_run_complete( Events\Run_Complete $event ): Events\Run_Complete {
		if ( $event->provider instanceof Database_Queue_Provider ) {
			$this->scheduler->schedule_next_run( $event->queue ?? 'default' );
		}

		return $event;
	}

	/**
	 * Increase the concurrency of the cron job with WordPress VIP's cron.
	 *
	 * @link https://docs.wpvip.com/technical-references/cron-control/#h-increasing-cron-event-concurrency
	 *
	 * @param array<string, int> $list List of events and their concurrency.
	 * @return array<string, int> List of events and their concurrency.
	 */
	#[Action( 'a8c_cron_control_concurrent_event_whitelist' )]
	public function increase_vip_concurrency( array $list ): array {
		$list[ Database_Scheduler::EVENT ] = 100;

		return $list;
	}
}
