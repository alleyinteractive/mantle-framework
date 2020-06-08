<?php
/**
 * Queue_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Contracts\Queue\Dispatcher as Dispatcher_Contract;
use Mantle\Framework\Contracts\Queue\Queue_Manager as Queue_Manager_Contract;
use Mantle\Framework\Queue\Dispatcher;
use Mantle\Framework\Queue\Queue_Manager;
use Mantle\Framework\Queue\Worker;
use Mantle\Framework\Queue\Wp_Cron_Provider;
use Mantle\Framework\Queue\Wp_Cron_Scheduler;
use Mantle\Framework\Service_Provider;

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
				$manager = new Queue_Manager( $app );
				$this->register_providers( $manager );
				return $manager;
			}
		);

		$this->app->singleton(
			'queue.worker',
			function ( $app ) {
				return new Worker( $app['queue'] );
			}
		);

		$this->app->singleton_if(
			Dispatcher_Contract::class,
			function( $app ) {
				return new Dispatcher( $app );
			}
		);
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
	public function register_providers( Queue_Manager_Contract $manager ) {
		$manager->add_provider( 'wordpress', Wp_Cron_Provider::class );

		// Setup the WordPress cron scheduler.
		Wp_Cron_Scheduler::register();
	}
}
