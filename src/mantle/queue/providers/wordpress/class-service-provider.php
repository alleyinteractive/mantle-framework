<?php
/**
 * Service_Provider class file
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

use Mantle\Queue\Console\Cleanup_Jobs_Command;
use Mantle\Queue\Events;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Service_Provider as Base_Service_Provider;
use Mantle\Scheduling\Schedule;

/**
 * WordPress Queue Service Provider Scheduler
 */
class Service_Provider extends Base_Service_Provider {
	/**
	 * Register the service provider.
	 */
	public function register(): void {
		// Register the queue admin service provider.
		if ( $this->app['config']->get( 'queue.enable_admin', true ) ) {
			$this->app->register( Admin\Service_Provider::class );
		}
	}

	/**
	 * Register the WordPress queue provider's post type and taxonomies.
	 *
	 * Registers the cleanup command with the application task scheduler to run
	 * daily (by default) to remove old queue jobs from the database.
	 */
	public function boot() {
		if ( did_action( 'init' ) ) {
			$this->register_data_types();
		}

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
	 * Register the WordPress queue provider's post type and taxonomies.
	 */
	#[Action( 'init' )]
	public function register_data_types(): void {
		Provider::register_data_types();
	}

	/**
	 * Listen for the providers registered event to register the WordPress queue
	 * provider.
	 *
	 * @param Events\Providers_Registered $event Event instance.
	 */
	#[Action( Events\Providers_Registered::class )]
	public function register_queue_provider( Events\Providers_Registered $event ): Events\Providers_Registered {
		$event->manager->add_provider( 'wordpress', Provider::class );

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
	#[Action( Scheduler::EVENT )]
	public function handle_scheduled_run( $queue = null ): void {
		Scheduler::run( $queue ?? 'default' );
	}

	/**
	 * Handle the Job Queued event to schedule the next cron run.
	 *
	 * @param Events\Job_Queued $event Job Queued event.
	 * @return Events\Job_Queued
	 */
	#[Action( Events\Job_Queued::class ) ]
	public function handle_job_queued_event( Events\Job_Queued $event ): Events\Job_Queued {
		if ( $event->provider instanceof Provider ) {
			Scheduler::on_job_queued( $event->queue ?? 'default' );
		}

		return $event;
	}

	/**
	 * Handle the Run Complete event to schedule the next cron run.
	 *
	 * @param Events\Run_Complete $event Run complete event.
	 * @return Events\Run_Complete
	 */
	#[Action( Events\Run_Complete::class ) ]
	public function handle_run_complete( Events\Run_Complete $event ): Events\Run_Complete {
		if ( $event->provider instanceof Provider ) {
			Scheduler::schedule_next_run( $event->queue ?? 'default' );
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
		$list[ Scheduler::EVENT ] = 100;

		return $list;
	}
}
