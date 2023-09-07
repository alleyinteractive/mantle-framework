<?php
/**
 * Service_Provider class file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

use Mantle\Queue\Events;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Service_Provider as Base_Service_Provider;

/**
 * WordPress Queue Service Provider Scheduler
 *
 * @todo Convert events to not need to return.
 */
class Service_Provider extends Base_Service_Provider {
	/**
	 * Register the WordPress queue provider's post type and taxonomies.
	 */
	public function boot() {
		if ( did_action( 'init' ) ) {
			$this->register_data_types();
		}
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
	 */
	#[Action( Scheduler::EVENT )]
	public function handle_scheduled_run( ...$args ): void {
		Scheduler::on_queue_run( ...$args );
	}

	/**
	 * Handle the Job Queued event to schedule the next cron run.
	 *
	 * @param Job_Queued $event Job Queued event.
	 */
	#[Action( Events\Job_Queued::class ) ]
	public function handle_job_queued_event( Events\Job_Queued $job ): Events\Job_Queued {
		if ( $job->provider instanceof Provider ) {
			Scheduler::schedule_next_run( $job->queue ?? 'default' );
		}

		return $job;
	}

	/**
	 * Handle the Run Complete event to schedule the next cron run.
	 *
	 * @param Run_Complete $event Run complete event.
	 */
	#[Action( Events\Run_Complete::class ) ]
	public function handle_run_complete( Events\Run_Complete $job ): Events\Run_Complete {
		if ( $job->provider instanceof Provider ) {
			Scheduler::schedule_next_run( $job->queue ?? 'default' );
		}

		return $job;
	}
}
