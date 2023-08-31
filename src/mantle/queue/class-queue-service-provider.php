<?php
/**
 * Queue_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Contracts\Queue\Dispatcher as Dispatcher_Contract;
use Mantle\Contracts\Queue\Queue_Manager as Queue_Manager_Contract;
use Mantle\Queue\Console\Run_Command;
use Mantle\Queue\Dispatcher;
use Mantle\Queue\Events\Job_Queued;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Queue_Manager;
use Mantle\Queue\Worker;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Service_Provider;

use function Mantle\Support\Helpers\tap;

/**
 * Queue Service Provider
 */
class Queue_Service_Provider extends Service_Provider {
	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->app->singleton_if(
			'queue',
			fn ( $app ) => tap(
				// Register the Queue Manager with the supported providers when invoked.
				new Queue_Manager( $app ),
				fn ( Queue_Manager $manager ) => $this->register_providers( $manager ),
			),
		);

		$this->app->singleton_if(
			'queue.worker',
			fn ( $app ) => new Worker( $app['queue'], $app['events'] ),
		);

		$this->app->singleton_if(
			Dispatcher_Contract::class,
			fn ( $app ) => new Dispatcher( $app ),
		);

		$this->add_command( Run_Command::class );
	}

	/**
	 * Boot the service provider.
	 */
	public function boot() {
		$this->app->make( Queue_Manager_Contract::class );
	}

	/**
	 * Register Queue Providers
	 *
	 * @param Queue_Manager_Contract $manager Queue Manager.
	 */
	protected function register_providers( Queue_Manager_Contract $manager ): void {
		$this->register_wordpress_provider( $manager );

		// Allow other plugins to register their own queue providers.
		$this->app['events']->dispatch( 'mantle_queue_register_providers', [ $manager ] );
	}

	/**
	 * Register the WordPress Cron Queue Provider
	 *
	 * @param Queue_Manager_Contract $manager Queue Manager.
	 */
	protected function register_wordpress_provider( Queue_Manager_Contract $manager ): void {
		$manager->add_provider( 'wordpress', Providers\WordPress\Provider::class );
	}

	/**
	 * Register the WordPress queue provider's post type and taxonomies.
	 *
	 * @return void
	 */
	#[Action( 'init' )]
	public function register_wordpress_provider_data_types(): void {
		Providers\WordPress\Provider::register_data_types();
	}

	/**
	 * Handle the schedule event to run the queue via WordPress cron.
	 */
	#[Action( Providers\WordPress\Scheduler::EVENT )]
	public function handle_wordpress_scheduled_run( ...$args ): void {
		Providers\WordPress\Scheduler::on_queue_run( ...$args );
	}

	/**
	 * Handle the Job Queued event to schedule the next cron run.
	 *
	 * @param Job_Queued $event Job Queued event.
	 */
	#[Action( Events\Job_Queued::class ) ]
	public function handle_wordpress_job_queued_event( $job ): void {
		// TODO: Fix typehint error once that is fixed.
		if ( $job instanceof Events\Job_Queued && $job->provider instanceof Providers\WordPress\Provider ) {
			Providers\WordPress\Scheduler::schedule_next_run( $job->queue ?? 'default' );
		}
	}

	/**
	 * Handle the Run Complete event to schedule the next cron run.
	 *
	 * @param Run_Complete $event Run complete event.
	 */
	#[Action( Events\Run_Complete::class ) ]
	public function handle_wordpress_run_complete( Run_Complete $job ): void {
		if ( $job->provider instanceof Providers\WordPress\Provider ) {
			Providers\WordPress\Scheduler::schedule_next_run( $job->queue ?? 'default' );
		}
	}
}
