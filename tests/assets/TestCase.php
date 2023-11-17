<?php

namespace Mantle\Tests\Assets;

use Asset_Manager_Scripts;
use Asset_Manager_Styles;
use Asset_Manager_Preload;
use Mantle\Assets\Asset_Manager;
use Mantle\Testing\Framework_Test_Case;
use WP_Scripts;

abstract class TestCase extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// Add test conditions
		remove_all_filters( 'am_asset_conditions', 10 );
		add_filter(
			'am_asset_conditions',
			function() {
				return [
					'global'            => true,
					'article_post_type' => true,
					'single'            => true,
					'archive'           => false,
					'has_slideshow'     => false,
					'has_video'         => false,
				];
			}
		);

		$this->reset_assets();

		Asset_Manager_Scripts::instance()->add_hooks();
		Asset_Manager_Scripts::instance()->manage_async();
		Asset_Manager_Styles::instance()->add_hooks();

		// Register with the container.
		$this->app->singleton(
			'asset.manager',
			fn() => new Asset_Manager(),
		);

		$this->app->alias( 'asset.manager', Asset_Manager::class );
		$this->app->alias( 'asset.manager', \Mantle\Contracts\Assets\Asset_Manager::class );
	}

	protected function get_wp_head(): string {
		ob_start();
		do_action( 'wp_head' );
		return ob_get_clean();
	}

	protected function reset_assets() {
		global $wp_scripts;

		$wp_scripts = new WP_Scripts();

		Asset_Manager_Scripts::instance()->assets           = [];
		Asset_Manager_Scripts::instance()->assets_by_handle = [];
		Asset_Manager_Scripts::instance()->asset_handles    = [];
		Asset_Manager_Styles::instance()->assets            = [];
		Asset_Manager_Styles::instance()->assets_by_handle  = [];
		Asset_Manager_Styles::instance()->asset_handles     = [];
		Asset_Manager_Styles::instance()->loadcss_added     = false;
		Asset_Manager_Preload::instance()->assets           = [];
		Asset_Manager_Preload::instance()->assets_by_handle = [];
		Asset_Manager_Preload::instance()->asset_handles    = [];
	}
}
