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
		$this->app->singleton_if(
			'asset.manager',
			fn() => new Asset_Manager(),
		);

		$this->app->alias( 'asset.manager', Asset_Manager::class );
		$this->app->alias( 'asset.manager', \Mantle\Contracts\Assets\Asset_Manager::class );

		$this->app->singleton_if(
			'asset.loader',
			fn () => new Asset_Loader(),
		);

		$this->app->alias( 'asset.loader', Asset_Loader::class );
	}

	/**
	 * Load the blocks from the application's `build/` folder.
	 *
	 * @return void
	 */
	protected function load_blocks() {
		$blocks = ( new Asset_Loader() )->blocks();

		foreach ( $blocks as $file ) {
			if ( file_exists( $file ) && 0 === validate_file( $file ) ) {
				require_once $file;
			}
		}
	}
}
