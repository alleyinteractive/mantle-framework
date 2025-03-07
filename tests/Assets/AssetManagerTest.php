<?php

namespace Mantle\Tests\Assets;

use Mantle\Assets\Asset_Manager;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group assets
 */
#[Group( 'assets' )]
class AssetManagerTest extends TestCase {
	public function test_register_script() {
		$manager = new Asset_Manager();
		$manager
			->script(
				"script-handle",
				"https://example.org/script.js",
				[
					"jquery",
				],
				"global",
				"sync",
			);

		$this->assertStringContainsString(
			"<script src=\"https://example.org/script.js\" id=\"script-handle-js\"></script>",
			$this->get_wp_head(),
		);
	}

	public function test_register_style() {
		$manager = new Asset_Manager();
		$manager
			->style(
				"style-handle",
				"https://example.org/style.css",
				[]
			);

		$this->assertStringContainsString(
			"<link rel='stylesheet' id='style-handle-css' href='https://example.org/style.css' media='all' />",
			$this->get_wp_head(),
		);
	}

	public function test_fluent_script() {
		$manager = new Asset_Manager();
		$manager
			->script( "example-fluent" )
			->src( "https://example.org/example-fluent.js" )
			->async();

		$this->assertStringContainsString(
			"<script src=\"https://example.org/example-fluent.js\" id=\"example-fluent-js\" async",
			$this->get_wp_head(),
		);
	}

	public function test_fluent_script_helper() {
		asset()
			->script( "example-helper" )
			->src( "https://example.org/example-helper.js" )
			->async();

		$this->assertStringContainsString(
			"<script src=\"https://example.org/example-helper.js\" id=\"example-helper-js\" async",
			$this->get_wp_head(),
		);
	}

	public function test_core_dependency() {
		global $wp_scripts;

		// Prevent a failing test if this is removed in the future.
		if ( ! isset( $wp_scripts->registered["swfobject"] ) ) {
			$this->markTestSkipped( "swfobject is not registered in core, should change the dependency tested against" );
			return;
		}

		$version = $wp_scripts->registered["swfobject"]->ver;

		// Get the core version of the asset.
		( new Asset_Manager() )
			->script( "swfobject" )
			->version( null )
			->async();

		$this->assertStringContainsString(
			"<script src=\"http://example.org/wp-includes/js/swfobject.js?ver={$version}\" id=\"swfobject-js\" async",
			$this->get_wp_head(),
		);
	}
}
