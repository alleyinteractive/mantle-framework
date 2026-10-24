<?php

namespace Mantle\Tests\Assets;

use Alley\WP\Asset_Manager\Scripts as Asset_Manager_Scripts;
use Mantle\Assets\Asset;
use Mantle\Assets\Asset_Manager;
use Mantle\Contracts\Assets\Load_Method;
use PHPUnit\Framework\Attributes\DataProvider;
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

		$head = $this->get_wp_head();

		$this->assertMatchesRegularExpression(
			'/\bid=("|\')script-handle-js\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/\bsrc=("|\')https:\/\/example\.org\/script\.js\\1/',
			$head,
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

		$head = $this->get_wp_head();

		$this->assertMatchesRegularExpression(
			'/\bid=("|\')example-fluent-js\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/\bsrc=("|\')https:\/\/example\.org\/example-fluent\.js\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/<script\b(?=[^>]*\bid=("|\')example-fluent-js\\1)(?=[^>]*\b(?:async(?:=(?:"async"|\'async\'))?|data-wp-strategy=(?:"async"|\'async\')))[^>]*>/i',
			$head,
		);
	}

	public function test_fluent_script_helper() {
		asset()
			->script( "example-helper" )
			->src( "https://example.org/example-helper.js" )
			->async();

		$head = $this->get_wp_head();

		$this->assertMatchesRegularExpression(
			'/\bid=("|\')example-helper-js\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/\bsrc=("|\')https:\/\/example\.org\/example-helper\.js\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/<script\b(?=[^>]*\bid=("|\')example-helper-js\\1)(?=[^>]*\b(?:async(?:=(?:"async"|\'async\'))?|data-wp-strategy=(?:"async"|\'async\')))[^>]*>/i',
			$head,
		);
	}

	public function test_core_dependency() {
		global $wp_scripts;

		// Prevent a failing test if this is removed in the future.
		if ( ! isset( $wp_scripts->registered["masonry"] ) ) {
			$this->markTestSkipped( "masonry is not registered in core, should change the dependency tested against" );
			return;
		}

		$version = $wp_scripts->registered["masonry"]->ver;

		( new Asset_Manager() )
			->script( "masonry" )
			->version( null )
			->async();

		$head = $this->get_wp_head();

		$this->assertMatchesRegularExpression(
			'/\bid=("|\')masonry-js\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/\bsrc=("|\')http:\/\/example\.org\/wp-includes\/js\/masonry\.min\.js\?ver=' . preg_quote( $version, '/' ) . '\\1/',
			$head,
		);

		$this->assertMatchesRegularExpression(
			'/<script\b(?=[^>]*\bid=("|\')masonry-js\\1)(?=[^>]*\b(?:async(?:=(?:"async"|\'async\'))?|data-wp-strategy=(?:"async"|\'async\')))[^>]*>/i',
			$head,
		);
	}

	/**
	 * @param callable(Asset): Asset $apply
	 */
	#[DataProvider( 'provide_async_and_defer' )]
	public function test_async_and_defer_load_as_async( callable $apply ) {
		$apply(
			asset()
				->script( 'example-async-defer' )
				->src( 'https://example.org/example-async-defer.js' )
		);

		$this->assertScriptLoadsAsync( 'example-async-defer' );
	}

	public static function provide_async_and_defer(): array {
		return [
			'async then defer' => [ fn ( Asset $asset ) => $asset->async()->defer() ],
			'defer then async' => [ fn ( Asset $asset ) => $asset->defer()->async() ],
		];
	}

	public function test_async_and_defer_keeps_async_with_blocking_dependent_on_asset_manager_1x() {
		if ( ! in_array( Load_Method::ASYNC_DEFER, Asset_Manager_Scripts::instance()->load_methods, true ) ) {
			$this->markTestSkipped( 'The async-defer load method requires wp-asset-manager 1.x.' );
		}

		asset()
			->script( 'example-async-defer' )
			->src( 'https://example.org/example-async-defer.js' )
			->async()
			->defer();

		asset()
			->script( 'example-dependent' )
			->src( 'https://example.org/example-dependent.js' )
			->dependencies( [ 'example-async-defer' ] );

		$this->assertMatchesRegularExpression(
			'/\sasync defer\s/',
			$this->get_script_tag( 'example-async-defer' ),
		);
	}

	public function test_deprecated_async_defer_load_method_loads_as_async() {
		new Asset(
			type: 'script',
			handle: 'example-constructor',
			src: 'https://example.org/example-constructor.js',
			load_method: Load_Method::ASYNC_DEFER,
		);

		$this->assertScriptLoadsAsync( 'example-constructor' );
	}

	public function test_deprecated_async_defer_modified_load_method_loads_as_async() {
		asset()
			->script( 'example-modified' )
			->src( 'https://example.org/example-modified.js' );

		asset()->load_method( 'example-modified', Load_Method::ASYNC_DEFER );

		$this->assertScriptLoadsAsync( 'example-modified' );
	}

	/**
	 * Get the script tag for a handle.
	 */
	protected function get_script_tag( string $handle ): string {
		$this->assertSame(
			1,
			preg_match( '/<script\b[^>]*\bid=("|\')' . preg_quote( $handle, '/' ) . '-js\1[^>]*>/i', $this->get_wp_head(), $matches ),
			"Script tag for {$handle} not found.",
		);

		return $matches[0];
	}

	/**
	 * Assert that a script tag loads with async.
	 */
	protected function assertScriptLoadsAsync( string $handle ): void {
		$this->assertMatchesRegularExpression( '/\sasync[\s>=]/', $this->get_script_tag( $handle ) );
	}
}
