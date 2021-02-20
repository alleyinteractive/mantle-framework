<?php
namespace Mantle\Tests\Framework\View;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Facade\View;

class Test_Php_Views extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		$this->app['view.loader']
			->clear_paths()
			->add_path( MANTLE_PHPUNIT_TEMPLATE_PATH . '/view', 'unit-test' );
	}

	public function test_get_container() {
		$this->assertEquals( $this->app, $this->app['view']->get_container() );
	}

	public function test_share_service_provider() {

		$this->view_factory = $this->app['view'];

		$this->assertEquals( 'default-value', $this->view_factory->shared( 'test-to-share', 'default-value' ) );

		// Share data as if it was from a service provider.
		View::share( 'test-to-share', 'the-value-to-compare' );

		$this->assertEquals( 'the-value-to-compare', $this->view_factory->shared( 'test-to-share', 'default-value' ) );
		$this->assertEquals( 'the-value-to-compare', $this->view_factory->get_shared()['test-to-share'] ?? '' );

		// Ensure you can get nested data.
		View::share(
			'nested-data',
			[
				'level0' => [
					'level1' => [
						'level2' => 'nested-value',
					],
				],
			]
		);

		$this->assertEquals( 'nested-value', $this->view_factory->shared( 'nested-data.level0.level1.level2' ) );
	}

	public function test_get_factory() {
		$this->assertEquals( $this->app['view'], $this->app['view']->shared( '__env' ) );
	}

	public function test_basic_load() {
		$contents = (string) view( 'basic' );
		$this->assertStringContainsString( 'Template loaded: successfully', $contents );
	}

	public function test_basic_load_name() {
		$contents = (string) view( 'basic', 'variant' );
		$this->assertStringContainsString( 'Variant loaded: successfully', $contents );
	}

	public function test_basic_load_name_fallback() {
		$contents = (string) view( 'basic', 'phony' );
		$this->assertStringContainsString( 'Template loaded: successfully', $contents );
	}

	public function test_basic_var() {
		$test = wp_rand();
		$contents = (string) view( 'basic', [ 'custom_var' => $test ] );
		$this->assertStringContainsString( "Template loaded: {$test}", $contents );
	}

	public function test_parent_child_load() {
		$contents = (string) view( 'parent' );
		$this->assertStringContainsString( 'Parent loaded: successfully', $contents );
		$this->assertStringContainsString( 'Child loaded: successfully', $contents );
	}

	public function test_iterate() {
		$items = [
			rand_str(),
			rand_str(),
			rand_str(),
		];
		$contents = iterate( $items, 'iterate-item' );
		foreach ( $items as $key => $value ) {
			$this->assertStringContainsString( "Item {$key}: {$value}", $contents );
		}
	}

	public function test_iterate_string_keys() {
		$items = [
			'one'   => rand_str(),
			'two'   => rand_str(),
			'three' => rand_str(),
		];
		$contents = iterate( $items, 'iterate-item' );
		foreach ( $items as $key => $value ) {
			$this->assertStringContainsString( "Item {$key}: {$value}", $contents );
		}
	}

	public function test_loop_array() {
		$post_ids = [
			static::factory()->post->create(),
			static::factory()->post->create(),
			static::factory()->post->create(),
		];

		$contents = loop( $post_ids, 'loop-post' );

		foreach ( $post_ids as $key => $post_id ) {
			$this->assertStringContainsString( "Post {$key}: {$post_id}", $contents );
		}
	}

	public function test_loop_query() {
		$posts = [
			static::factory()->post->create(
				[
					'post_date' => '2003-01-01 00:00:00',
					'meta' => [
						'loop_query' => 1,
					],
					]
			),
			static::factory()->post->create(
				[
					'post_date' => '2002-01-01 00:00:00',
					'meta' => [
						'loop_query' => 1,
					],
					]
			),
			static::factory()->post->create(
				[
					'post_date' => '2001-01-01 00:00:00',
					'meta' => [
						'loop_query' => 1,
					],
					]
			),
		];

		$contents = loop( new \WP_Query( 'orderby=date&order=desc&meta_key=loop_query&meta_value=1' ), 'loop-post' );
		foreach ( $posts as $key => $post_id ) {
			$this->assertStringContainsString( "Post {$key}: {$post_id}", $contents );
		}
	}

	public function test_reset_post() {
		$posts = [
			static::factory()->post->create( [ 'post_date' => '2016-01-01 00:00:00' ] ),
			static::factory()->post->create( [ 'post_date' => '2015-03-01 00:00:00' ] ),
			static::factory()->post->create( [ 'post_date' => '2015-02-01 00:00:00' ] ),
			static::factory()->post->create( [ 'post_date' => '2015-01-01 00:00:00' ] ),
		];

		global $post;
		$post = get_post( $posts[0] );
		setup_postdata( $post );
		$this->assertSame( $posts[0], get_the_ID() );

		$contents = loop( new \WP_Query( 'year=2015' ), 'loop-post' );
		foreach ( [ 1, 2, 3 ] as $i => $key ) {
			$this->assertStringContainsString( "Post {$i}: {$posts[ $key ]}", $contents );
		}

		$this->assertSame( $posts[0], get_the_ID() );
	}

	public function test_loop_array_string_keys() {
		$posts = [
			'one' => static::factory()->post->create(),
			'two' => static::factory()->post->create(),
			'three' => static::factory()->post->create(),
		];
		$contents = loop( $posts, 'loop-post' );
		foreach ( $posts as $key => $post_id ) {
			$this->assertStringContainsString( "Post {$key}: {$post_id}", $contents );
		}
	}

	public function test_nested_loops() {
		$create_post = function( $date ) {
			return static::factory()->post->create(
				[
					'post_date' => $date,
					'meta' => [
						'nested_loop' => 1,
					],
				]
			);
		};

		$posts = [
			$create_post( '2016-01-01 00:00:00' ),

			$create_post( '2015-01-03 00:00:00' ),
			$create_post( '2015-01-02 00:00:00' ),
			$create_post( '2015-01-01 00:00:00' ),

			$create_post( '2014-01-03 00:00:00' ),
			$create_post( '2014-01-02 00:00:00' ),
			$create_post( '2014-01-01 00:00:00' ),
		];

		global $post;
		$post = get_post( $posts[0] );
		setup_postdata( $post );

		$this->assertSame( $posts[0], get_the_ID() );

		$contents = loop(
			new \WP_Query( 'year=2015orderby=date&order=desc&meta_key=nested_loop&meta_value=1' ),
			'loop',
			[
				'child_query' => new \WP_Query( 'year=2014&orderby=date&order=desc&meta_key=nested_loop&meta_value=1' ),
			]
		);

		$subloop = [
			"[Post 0: {$posts[4]}]",
			"[Post 1: {$posts[5]}]",
			"[Post 2: {$posts[6]}]",
		];

		$expected = [];
		foreach ( [ 1, 2, 3 ] as $key => $i ) {
			$expected[] = sprintf( "[Parent loop post %s: %d]\n%s\n", $key, $posts[ $i ], implode( "\n", $subloop ) );
		}
		$expected = implode( $expected );

		$this->assertSame( $expected, $contents );
		$this->assertSame( $posts[0], get_the_ID() );
		$this->assertNull( $this->app['view']->get_current() );
	}

	public function test_empty_post_restoring() {
		unset( $GLOBALS['post'] );

		$parent_post = static::factory()->post->create( [ 'post_date' => '2016-01-01 00:00:00' ] );
		$child_post = static::factory()->post->create( [ 'post_date' => '2015-01-03 00:00:00' ] );

		$contents = loop(
			[ $parent_post ],
			'loop',
			[
				'child_query' => [ $child_post ],
			]
		);

		$this->assertSame( "[Parent loop post 0: {$parent_post}]\n[Post 0: {$child_post}]\n", $contents );
		$this->assertSame( null, $GLOBALS['post'] );
	}

	public function test_basic_cache_load() {
		$slug = 'cache';

		// Set a dynamic value used in the template
		$_SERVER['__mantle_cache_data'] = rand_str();
		$original_rand = $_SERVER['__mantle_cache_data'];

		// Load the partial, verify it works and the transient gets set
		$contents = (string) view( $slug )->cache();
		$this->assertSame( "Template loaded: {$_SERVER['__mantle_cache_data']}", $contents );

		// Change the dynamic value used in the partial.
		$_SERVER['__mantle_cache_data'] = rand_str();
		$new_rand = $_SERVER['__mantle_cache_data'];

		// Verify that the value has changed when not loading from cache.
		$contents = (string) view( $slug );
		$this->assertSame( "Template loaded: {$new_rand}", $contents );

		// ... but if we load the cached variant, it should give the old value
		$contents = (string) view( $slug )->cache();
		$this->assertSame( "Template loaded: {$original_rand}", $contents );

		// Ensure the view isn't cached when passing in a different arguments.
		$_SERVER['__mantle_cache_data'] = rand_str();
		$new_rand = $_SERVER['__mantle_cache_data'];

		$contents = (string) view( $slug, [ 'different' => true ] )->cache();
		$this->assertSame( "Template loaded: {$new_rand}", $contents );
	}

	public function test_cache_load_custom_key() {
		$slug = 'cache';
		$key = 'cached_partials_test';

		// Set a dynamic value used in the template
		$rand = rand_str();
		$_SERVER['__mantle_cache_data'] = $rand;

		// Load the partial, verify it works and the transient gets set
		$contents = (string) view( $slug )->cache( 1000, $key );
		$this->assertSame( "Template loaded: {$rand}", $contents );
		$this->assertSame( "Template loaded: {$rand}", get_transient( $key ) );

		// Change the dynamic value used in the partial
		$new_rand = rand_str();
		$_SERVER['__mantle_cache_data'] = $new_rand;

		// Ensure that we get a cached response
		$contents = (string) view( $slug )->cache( 1000, $key );
		$this->assertSame( "Template loaded: {$rand}", $contents );
		$this->assertSame( "Template loaded: {$rand}", get_transient( $key ) );

		// Delete the transient
		delete_transient( $key );

		// Verify that the value has changed
		$contents = (string) view( $slug )->cache( 1000, $key );
		$this->assertSame( "Template loaded: {$new_rand}", $contents );
		$this->assertSame( "Template loaded: {$new_rand}", get_transient( $key ) );
	}
}
