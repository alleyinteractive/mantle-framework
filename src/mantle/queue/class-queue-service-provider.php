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
use Mantle\Queue\Events\Job_Processed;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Queue_Manager;
use Mantle\Queue\Worker;
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
		$this->app->singleton(
			'queue',
			function ( $app ) {
				// Register the Queue Manager with the supported providers when invoked.
				return tap(
					new Queue_Manager( $app ),
					fn ( $manager ) => $this->register_providers( $manager ),
				);
			}
		);

		$this->app->singleton(
			'queue.worker',
			function ( $app ) {
				return new Worker( $app['queue'], $app['events'] );
			}
		);

		$this->app->singleton_if(
			Dispatcher_Contract::class,
			function( $app ) {
				return new Dispatcher( $app );
			}
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
	protected function register_providers( Queue_Manager_Contract $manager ) {
		$this->register_wp_cron_provider( $manager );
	}

	/**
	 * Register the WordPress Cron Queue Provider
	 *
	 * @param Queue_Manager_Contract $manager Queue Manager.
	 */
	protected function register_wp_cron_provider( Queue_Manager_Contract $manager ) {
		$manager->add_provider( 'wordpress', Providers\WordPress\Provider::class );

		// Setup the WordPress cron scheduler.
		\add_action( 'init', [ Providers\WordPress\Provider::class, 'on_init' ] );
		\add_action( Providers\WordPress\Scheduler::EVENT, [ Providers\WordPress\Scheduler::class, 'on_queue_run' ] );

		// Add the event listener to schedule the next cron run.
		$this->app['events']->listen(
			Run_Complete::class,
			function( Events\Run_Complete $event ) {
				if ( $event->provider instanceof Providers\WordPress\Provider ) {
					Providers\WordPress\Scheduler::schedule_next_run( $event->queue );
				}
			}
		);
	}
}
