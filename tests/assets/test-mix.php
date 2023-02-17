<?php

namespace Mantle\Tests\Assets;

use Mantle\Assets\Asset_Manager;
use Mantle\Assets\Exception\Mix_File_Not_Found;
use Mantle\Assets\Mix;

/**
 * @group assets
 */
class Test_Mix extends Test_Case {
	public string $manifest_dir = '';

	protected Mix $mix;

	protected function setUp(): void {
		parent::setUp();

		$this->manifest_dir = realpath( __DIR__ . '/../fixtures/assets-mix/' );

		$this->mix = new Mix( $this->manifest_dir );
	}

	public function test_read_manifest_path() {
		$this->assertEquals(
			'/build/entries-app.js?id=1970f20b5b729b06ff3752474e5c2d3c',
			$this->mix->path( '/entries-app.js' )
		);

		$this->assertEquals(
			'/build/app.js?id=9a873e9f8d1bb6e3b4331bd8ae5ef7cc',
			$this->mix->path( '/app.js' )
		);
	}

	public function test_read_manifest_path_invoke() {
		$mix = $this->mix;

		$this->assertEquals(
			'/build/entries-app.js?id=1970f20b5b729b06ff3752474e5c2d3c',
			$mix( '/entries-app.js' )
		);

		$this->assertEquals(
			'/build/app.js?id=9a873e9f8d1bb6e3b4331bd8ae5ef7cc',
			$mix( '/app.js' )
		);
	}

	public function test_read_unknown_file() {
		$this->expectException( Mix_File_Not_Found::class );

		$this->mix->path( '/unknown.js' );
	}

	public function test_read_unknown_manifest() {
		$this->expectException( Mix_File_Not_Found::class );

		$mix = new Mix( __DIR__ );

		$mix->path( '/app.js' );
	}

	public function test_read_hot_manifest() {
		$mix = new Mix( $this->manifest_dir . '/hot/' );

		$this->assertEquals(
			'//localhost:8080/entries-app.js',
			$mix->path( '/entries-app.js' )
		);

		$this->assertEquals(
			'//localhost:8080/app.css',
			$mix->path( '/app.css' )
		);
	}

	public function test_read_asset_dependencies() {
		$this->assertEquals(
			[ 'wp-data' ],
			$this->mix->dependencies( '/blocks-example-block.js' ),
		);

		$this->assertEquals(
			[ 'wp-data' ],
			$this->mix->dependencies( '/blocks-example-block' ),
		);
	}

	public function test_read_missing_asset_dependencies() {
		$this->assertEquals(
			[],
			$this->mix->dependencies( '/unknown-entry.js' ),
		);

		$this->assertEquals(
			[],
			$this->mix->dependencies( '/unknown-entry' ),
		);
	}
}
