<?php
/**
 * App_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Application;

use Mantle\Contracts\Application;
use Mantle\Scheduling\Schedule;
use Mantle\Support\Service_Provider;

use function Mantle\Support\Helpers\tap;

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
			fn () => $this->boot_scheduler(),
		);
	}

	/**
	 * Boot the scheduler service.
	 */
	protected function boot_scheduler() {
		$this->app->singleton(
			Schedule::class,
			fn ( $app ) => tap(
				new Schedule( $app ),
				fn ( Schedule $schedule ) => $this->schedule( $schedule ),
			),
		);

		Schedule::schedule_cron_event();
	}

	/**
	 * Define the application's command schedule.
	 *
	 * @param Schedule $schedule Schedule instance.
	 */
	protected function schedule( $schedule ) { }
}
