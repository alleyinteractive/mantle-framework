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
}
