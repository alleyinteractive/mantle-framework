<?php

namespace Mantle\Tests\Assets;

use Mantle\Assets\Asset_Loader;
use Mantle\Assets\Exception\Asset_Not_Found;

class AssetLoaderTest extends TestCase {
	public string $build_dir = '';

	public Asset_Loader $loader;

	protected function setUp(): void {
		parent::setUp();

		$build_dir = realpath( __DIR__ . '/../fixtures/assets/' );

		$this->assertIsString( $build_dir );

		$this->build_dir = $build_dir;

		$this->loader = new Asset_Loader( $this->build_dir, 'https://example.org/base' );

		app()['asset.loader'] = $this->loader;
		app()->alias( 'asset.loader', Asset_Loader::class );
	}

	public function test_read_path(): void {
		$this->assertSame(
			'/app.js?id=8c5b220bf6f482881a90',
			$this->loader->path( '/app.js' ),
		);

		$this->assertSame(
			'/app.css?id=8c5b220bf6f482881a90',
			$this->loader->path( '/app.css' ),
		);
	}

	public function test_read_path_subdir(): void {
		$this->assertSame(
			'/example-entry/index.js?id=534dc549a5fb03be27a1',
			$this->loader->path( '/example-entry/index.js' ),
		);
	}

	public function test_read_script_url(): void {
		$this->assertSame(
			'https://example.org/base/app.js?id=8c5b220bf6f482881a90',
			$this->loader->url( '/app.js' ),
		);
	}

	public function test_unknown_asset(): void {
		$this->expectException( Asset_Not_Found::class );

		$this->loader->path( '/unknown.js' );
	}

	public function test_invoke(): void {
		$loader = $this->loader;

		$this->assertSame(
			'https://example.org/base/app.js?id=8c5b220bf6f482881a90',
			$loader( '/app.js' ),
		);
	}

	public function test_read_blocks(): void {
		$this->assertSame(
			[
				$this->build_dir . '/example-block/index.php',
			],
			$this->loader->blocks(),
		);
	}

	public function test_asset_loader_helper(): void {
		$this->assertSame(
			'https://example.org/base/app.js?id=8c5b220bf6f482881a90',
			asset_loader( '/app.js' ),
		);
	}

	public function test_asset_enqueue_from_asset_path(): void {
		asset()->script( '/app.js' )->async();

		$head = $this->get_wp_head();

		$this->assertMatchesRegularExpression(
			'/\bid=("|\')app-js-js\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/\bsrc=("|\')https:\/\/example\.org\/base\/app\.js\?id=8c5b220bf6f482881a90\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/<script\b(?=[^>]*\bid=("|\')app-js-js\\1)(?=[^>]*\b(?:async(?:=(?:"async"|\'async\'))?|data-wp-strategy=(?:"async"|\'async\')))[^>]*>/i',
			$head,
		);

		// Ensure that wp-blocks was loaded as well (as a dependency).
		$this->assertStringContainsString( 'wp-blocks-js', $head );
	}

	public function test_asset_enqueue_no_error_if_not_found(): void {
		asset()->script( '/unknown.js' )->async();

		// Ensure that no exception was thrown.
	}
}
