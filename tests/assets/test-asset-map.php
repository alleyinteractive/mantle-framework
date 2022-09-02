<?php

namespace Mantle\Tests\Assets;

use Mantle\Assets\Asset;

/**
 * @group assets
 */
class Test_Asset_Map extends Test_Case {
	public function test_asset_map_retrieval() {
		$this->assertNotEmpty( asset_map( 'example-entry.js' ) );
		$this->assertEquals( 'example-entry.bundle.min.js', asset_map( 'example-entry.js', 'path' ) );
		$this->assertEquals( '14b07c93c228c31a4b1a', asset_map( 'example-entry.js', 'hash' ) );

		$this->assertNull( asset_map( 'unknown.js', 'path' ) );
	}

	public function test_asset_map_path() {
		$this->assertStringEndsWith( '/build/example-entry.bundle.min.js', asset_map()->path( 'example-entry.js' ) );
		$this->assertNull( asset_map()->path( 'unknown.js' ) );
	}

	public function test_asset_map_hash() {
		$this->assertEquals( '14b07c93c228c31a4b1a', asset_map()->hash( 'example-entry.js' ) );

		// Should fallback to the asset map's hash.
		$this->assertNotNull( asset_map()->hash( 'unknown.js' ) );
	}

	public function test_asset_map_enqueue_script() {
		$this->assertInstanceOf(
			Asset::class,
			asset_map()->enqueue( 'example-entry.js' )
		);

		$this->assertHeadContains(
			"<script src='http://example.org/wp-content/plugins/mantle/build/example-entry.bundle.min.js' id='example-entry-js'></script>",
		);
	}

	public function test_asset_map_enqueue_style() {
		$this->assertInstanceOf(
			Asset::class,
			asset_map()->enqueue( 'example-entry.css' )
		);

		$this->assertHeadContains(
			"href='http://example.org/wp-content/plugins/mantle/build/example-entry.min.css' media='all",
		);
	}

	public function test_asset_map_enqueue_unknown() {
		$this->assertNull( asset_map()->enqueue( 'unknown-entry.js' ) );
	}
}
