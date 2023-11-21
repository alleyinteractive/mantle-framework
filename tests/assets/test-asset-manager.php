<?php

namespace Mantle\Tests\Assets;

use Mantle\Assets\Asset_Manager;

/**
 * @group assets
 */
class Test_Asset_Manager extends Test_Case {
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
			"<link rel='stylesheet' id='style-handle-css' href='https://example.org/style.css' media='all' />",
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
}
