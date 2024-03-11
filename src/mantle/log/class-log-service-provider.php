<?php
/**
 * Log_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Log;

use Mantle\Support\Service_Provider;

/**
 * Log Service Provider
 */
class Log_Service_Provider extends Service_Provider {
	/**
	 * Register the commands.
	 */
	public function register(): void {
		$this->app->singleton(
			'log',
			fn ( $app) => new Log_Manager( $app, $app['events'] )
		);
	}
}
