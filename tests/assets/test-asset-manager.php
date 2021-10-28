<?php

namespace Mantle\Tests\Assets;

use Asset_Manager_Scripts;
use Asset_Manager_Styles;
use Asset_Manager_Preload;
use Mantle\Assets\Asset_Manager;
use Mantle\Testing\Framework_Test_Case;
use WP_Scripts;

class Test_Asset_Manager extends Framework_Test_Case {
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

	public function test_register_script() {
		$manager = new Asset_Manager();
		$manager
			->script(
				'script-handle',
				'https://example.org/script.js',
				[
					'jquery',
				],
				'global',
				'sync',
			);

		$this->assertStringContainsString(
			"<script src='https://example.org/script.js' id='script-handle-js'></script>",
			$this->get_wp_head(),
		);
	}

	public function test_register_style() {
		$manager = new Asset_Manager();
		$manager
			->style(
				'style-handle',
				'https://example.org/style.css',
				[]
			);

		$this->assertStringContainsString(
			"<link rel='stylesheet' id='style-handle-css'  href='https://example.org/style.css' media='all' />",
			$this->get_wp_head(),
		);
	}

	public function test_async_script() {
		$manager = new Asset_Manager();
		$manager
			->script(
				'testsync-script-handle',
				'https://example.org/example-script.js',
			);

		$manager->async( 'testsync-script-handle' );

		$this->assertStringContainsString(
			"<script async src='https://example.org/example-script.js' id='testsync-script-handle-js'></script>",
			$this->get_wp_head(),
		);
	}

	public function test_fluent_script() {
		$manager = new Asset_Manager();
		$manager
			->script( 'example-fluent' )
			->src( 'https://example.org/example-fluent.js' )
			->async();

		$this->assertStringContainsString(
			"<script async src='https://example.org/example-fluent.js' id='example-fluent-js'></script>",
			$this->get_wp_head(),
		);
	}

	public function test_fluent_script_helper() {
		asset()
			->script( 'example-helper' )
			->src( 'https://example.org/example-helper.js' )
			->async();

		$this->assertStringContainsString(
			"<script async src='https://example.org/example-helper.js' id='example-helper-js'></script>",
			$this->get_wp_head(),
		);
	}

	public function test_core_dependency() {
		global $wp_scripts;

		// Prevent a failing test if this is removed in the future.
		if ( ! isset( $wp_scripts->registered['swfobject'] ) ) {
			$this->markTestSkipped( 'swfobject is not registered in core, should change the dependency tested against' );
			return;
		}

		$version = $wp_scripts->registered['swfobject']->ver;

		// Get the core version of the asset.
		( new Asset_Manager() )
			->script( 'swfobject' )
			->version( null )
			->async();

		$this->assertStringContainsString(
			"<script async src='http://example.org/wp-includes/js/swfobject.js?ver={$version}' id='swfobject-js'></script>",
			$this->get_wp_head(),
		);
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
