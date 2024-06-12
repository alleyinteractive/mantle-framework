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
 *
 * This provider is always loaded by the framework and does not need to be
 * declared. It is registered before the providers are booted to allow for the
 * application to extend the provider with custom functionality.
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
			'scheduler',
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
