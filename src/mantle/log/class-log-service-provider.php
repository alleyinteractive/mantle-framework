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
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton(
			'log',
			function( $app ) {
				return new Log_Manager( $app, $app['events'] );
			}
		);
	}
}
