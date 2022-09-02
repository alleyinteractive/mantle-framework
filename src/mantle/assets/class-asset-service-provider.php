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
	 */
	public function register() {
		$this->app->singleton( 'asset.manager', fn() => new Asset_Manager() );
		$this->app->singleton( 'asset.map', fn() => Asset_Map::default_map() );

		$this->app->alias( 'asset.manager', Asset_Manager::class );
		$this->app->alias( 'asset.manager', \Mantle\Contracts\Assets\Asset_Manager::class );
		$this->app->alias( 'asset.map', Asset_Map::class );
	}
}
