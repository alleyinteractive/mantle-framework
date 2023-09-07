<?php
/**
 * Queue_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Contracts\Queue\Queue_Manager as Queue_Manager_Contract;
use Mantle\Queue\Console\Run_Command;
use Mantle\Queue\Dispatcher;
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
			'queue.dispatcher',
			fn ( $app ) => new Dispatcher( $app ),
		);

		$this->add_command( Run_Command::class );

		// Register the queue service providers.
		$this->app->register( Providers\WordPress\Service_Provider::class );
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
	 * Fire an event to allow other plugins to register queue providers.
	 *
	 * @param Queue_Manager_Contract $manager Queue Manager.
	 */
	protected function register_providers( Queue_Manager_Contract $manager ): void {
		$this->app['events']->dispatch(
			new Events\Providers_Registered( $manager ),
		);
	}
}
