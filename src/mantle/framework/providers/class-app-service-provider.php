<?php
/**
 * App_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Contracts\Application;
use Mantle\Scheduling\Schedule;
use Mantle\Framework\Service_Provider;

use function Mantle\Framework\Helpers\tap;

/**
 * App Service Provider
 */
class App_Service_Provider extends Service_Provider {
	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;

		$this->app->booted(
			function() {
				$this->boot_scheduler();
			}
		);
	}

	/**
	 * Boot the scheduler service.
	 */
	protected function boot_scheduler() {
		$this->app->singleton(
			Schedule::class,
			function( $app ) {
				return tap(
					new Schedule( $app ),
					function( $schedule ) {
						$this->schedule( $schedule );
					}
				);
			}
		);

		// Setup the cron event for the scheduler.
		Schedule::schedule_cron_event();
	}

	/**
	 * Define the application's command schedule.
	 *
	 * @param Schedule $schedule Schedule instance.
	 */
	protected function schedule( $schedule ) { }
}
