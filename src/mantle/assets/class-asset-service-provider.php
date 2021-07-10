<?php
/**
 * Asset_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use Mantle\Support\Service_Provider;

/**
 * Asset Service Provider
 */
abstract class Asset_Service_Provider extends Service_Provider {
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton(
			'asset.manager',
			fn() => new Asset_Manager()
		);
	}
}
