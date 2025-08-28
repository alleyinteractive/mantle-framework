<?php
/**
 * App_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Application;

use Mantle\Contracts\Application;
use Mantle\Scheduling\Schedule;
use Mantle\Support\Attributes\Filter;
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
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( protected Application $app ) {}

	/**
	 * Register the application service provider
	 */
	public function register(): void {
		$this->app->singleton( 'scheduler', function ( Application $app ): Schedule {
			$schedule = new Schedule( $app );

			$this->schedule( $schedule );

			return $schedule;
		} );
	}

	/**
	 * Boot the scheduler service.
	 */
	public function boot(): void {
		$this->app->make( 'scheduler' )->schedule_cron_event();
	}

	/**
	 * Define the application's command schedule.
	 *
	 * Used for legacy command schedule registration. The preferred new way is to
	 * the use Schedule facade.
	 *
	 * @param Schedule $schedule Schedule instance.
	 */
	protected function schedule( Schedule $schedule ): void {}

	/**
	 * Add a cron schedule for the schedule class.
	 *
	 * @param array<string, array{interval: int, display: string}> $schedules
	 * @return array<string, array{interval: int, display: string}> $schedules
	 */
	#[Filter( 'cron_schedules' )]
	public function add_cron_schedule( array $schedules ): array {
		$schedules['mantle_schedule_every_minute'] = [
			'interval' => MINUTE_IN_SECONDS,
			'display'  => 'Every Minute',
		];

		return $schedules;
	}
}
