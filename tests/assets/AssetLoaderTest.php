<?php

namespace Mantle\Tests\Assets;

use Mantle\Assets\Asset_Loader;
use Mantle\Assets\Exception\Asset_Not_Found;

class AssetLoaderTest extends TestCase {
	public string $build_dir = '';

	public Asset_Loader $loader;

	protected function setUp(): void {
		parent::setUp();

		$this->build_dir = realpath( __DIR__ . '/../fixtures/assets/' );

		$this->loader = new Asset_Loader( $this->build_dir, 'https://example.org/base' );

		$this->app['asset.loader'] = $this->loader;
		$this->app->alias( 'asset.loader', Asset_Loader::class );
	}

	public function test_read_path() {
		$this->assertEquals(
			'/app.js?id=8c5b220bf6f482881a90',
			$this->loader->path( '/app.js' ),
		);

		$this->assertEquals(
			'/app.css?id=8c5b220bf6f482881a90',
			$this->loader->path( '/app.css' ),
		);
	}

	public function test_read_path_subdir() {
		$this->assertEquals(
			'/example-entry/index.js?id=534dc549a5fb03be27a1',
			$this->loader->path( '/example-entry/index.js' ),
		);
	}

	public function test_read_script_url() {
		$this->assertEquals(
			'https://example.org/base/app.js?id=8c5b220bf6f482881a90',
			$this->loader->url( '/app.js' ),
		);
	}

	public function test_unknown_asset() {
		$this->expectException( Asset_Not_Found::class );

		$this->loader->path( '/unknown.js' );
	}

	public function test_invoke() {
		$loader = $this->loader;

		$this->assertEquals(
			'https://example.org/base/app.js?id=8c5b220bf6f482881a90',
			$loader( '/app.js' ),
		);
	}

	public function test_read_blocks() {
		$this->assertEquals(
			[
				$this->build_dir . '/example-block/index.php',
			],
			$this->loader->blocks(),
		);
	}

	public function test_asset_loader_helper() {
		$this->assertEquals(
			'https://example.org/base/app.js?id=8c5b220bf6f482881a90',
			asset_loader( '/app.js' ),
		);
	}

	public function test_asset_enqueue_from_asset_path() {
		asset()->script( '/app.js' )->async();

		$head = $this->get_wp_head();

		$this->assertStringContainsString(
			"<script async src='https://example.org/base/app.js?id=8c5b220bf6f482881a90' id='app-js-js'></script>",
			$head,
		);

		// Ensure that wp-blocks was loaded as well (as a dependency).
		$this->assertStringContainsString( 'wp-blocks-js', $head );
	}

	public function test_asset_enqueue_no_error_if_not_found() {
		asset()->script( '/unknown.js' )->async();

		// Ensure that no exception was thrown.
		$this->assertTrue( true );
	}
}
