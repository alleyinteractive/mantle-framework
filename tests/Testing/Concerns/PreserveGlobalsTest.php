<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\Attributes\DisableGlobalPreservation;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use WP_Rewrite;
use function Mantle\Support\Helpers\collect;

/**
 * Tests for preserving global state between tests.
 *
 * Ensure that tests cannot cross contaminate each other by modifying global
 * state, unless explicitly allowed.
 *
 * @group testing
 */
#[Group( 'testing' )]
class PreserveGlobalsTest extends FrameworkTestCase {
	/**
	 * Register meta that should still be available in each test run.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Register a meta key to test that it persists between tests.
		register_meta( 'post', 'example_meta_key', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		] );

		register_post_type( 'persistent_post_type' );

		add_rewrite_rule( '^persistent-rule/?$', 'index.php?persistent=1', 'top' );
		add_rewrite_tag( '%persistent-tag%', '([0-9]+)' );
		flush_rewrite_rules();
	}

	/**
	 * Run before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->skip_for_phpunit_version( '10.0.0', '<', 'Global preservation is not supported in this PHPUnit version.' );
	}

	/**
	 * Ensure that meta keys registered in one test are still registered in
	 * another.
	 *
	 * Previously, there was an issue where the registered meta keys would be
	 * cleared between tests, causing tests that relied on them to fail. This test
	 * ensures that the registered meta keys persist between tests.
	 *
	 * We also want to ensure that meta registered by the test itself is NOT
	 * preserved. We want a clean slate at the start of each test.
	 *
	 * @dataProvider dataprovider_twice
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_meta_keys_preserved_between_tests(): void {
		$this->assertTrue( registered_meta_key_exists( 'post', 'example_meta_key' ) );

		$this->assertFalse(
			registered_meta_key_exists( 'post', 'temporary_meta_key' ),
			'Temporary meta key should not exist at the start of the test.'
		);

		// Register a new meta key for this test.
		register_meta( 'post', 'temporary_meta_key', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		] );

		$this->assertTrue(
			registered_meta_key_exists( 'post', 'temporary_meta_key' ),
			'Temporary meta key should be registered within the test.'
		);
	}

	/**
	 * Register a post type that will be tested in
	 * test_disable_global_preservation_part_two().
	 */
	#[DisableGlobalPreservation]
	public function test_disable_global_preservation(): void {
		register_post_type( 'temporary_post_type' );

		$this->assertTrue( post_type_exists( 'temporary_post_type' ) );
	}

	/**
	 * Assert that the post type registered in test_disable_global_preservation()
	 * is persisted because global preservation is disabled.
	 */
	#[DisableGlobalPreservation]
	public function test_disable_global_preservation_part_two(): void {
		$this->assertTrue( post_type_exists( 'temporary_post_type' ) );
	}

	/**
	 * Ensure that post types registered in one test are NOT preserved in
	 * another.
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_post_types_are_isolated_between_tests(): void {
		// Post type registered in the test itself should not exist at the start of the test.
		$this->assertFalse( post_type_exists( 'isolated_post_type' ) );

		register_post_type( 'isolated_post_type' );

		$this->assertTrue( post_type_exists( 'isolated_post_type' ) );

		// Post type registered in setUpBeforeClass() should persist.
		$this->assertTrue( post_type_exists( 'persistent_post_type' ) );
	}

	/**
	 * Ensure that rewrite extra permastructs are preserved and originally stored correctly.
	 *
	 * @dataProvider dataprovider_twice
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_rewrite_extra_permastructs_are_preserved(): void {
		global $wp_rewrite;

		$this->assertNotEmpty( $wp_rewrite->extra_permastructs, 'Expected current globals to have extra_permastructs.' );
		$this->assertNotEmpty( self::$current_class_globals['wp_rewrite']->extra_permastructs, 'Expected current class globals to have extra_permastructs.' );
		$this->assertNotEmpty( self::$original_globals['wp_rewrite']->extra_permastructs, 'Expected original globals to have extra_permastructs.' );
	}

	/**
	 * Ensure that rewrite rules added in one test are NOT preserved in
	 * another.
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_rewrite_rules_are_isolated_between_tests(): void {
		$rules = get_option( 'rewrite_rules' );
		$this->assertIsArray( $rules );
		$this->assertArrayNotHasKey( '^temporary-rule/?$', $rules );

		add_rewrite_rule( '^temporary-rule/?$', 'index.php?temporary=1', 'top' );
		flush_rewrite_rules();

		$rules = get_option( 'rewrite_rules' );
		$this->assertIsArray( $rules );
		$this->assertArrayHasKey( '^temporary-rule/?$', $rules );

		// Persistent rule added in setUpBeforeClass() should persist.
		$this->assertArrayHasKey( '^persistent-rule/?$', $rules );
	}

	/**
	 * Ensure that rewrite tags added in one test are NOT preserved in
	 * another.
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_rewrite_tags_are_isolated_between_tests(): void {
		// Test against the raw backed up globals to ensure that the test is valid early on.
		$this->assertArrayHasKey( 'wp_rewrite', static::$current_class_globals ?: [] );
		$this->assertNotContains( 'temporary-tag=', static::$current_class_globals['wp_rewrite']->queryreplace );

		global $wp_rewrite, $wp;

		$this->assertInstanceOf( WP_Rewrite::class, $wp_rewrite );

		// Ensure the persistent tag exists.
		$this->assertContains( 'persistent-tag=', $wp_rewrite->queryreplace );
		$this->assertContains( '%persistent-tag%', $wp_rewrite->rewritecode );

		// Ensure that the query variable is added to $wp->public_query_vars.
		$this->assertContains( 'persistent-tag', $wp->public_query_vars );

		// Ensure the temporary tag does not exist.
		$this->assertNotContains( 'temporary-tag=', $wp_rewrite->queryreplace );
		$this->assertNotContains( '%temporary-tag%', $wp_rewrite->rewritecode );

		add_rewrite_tag( '%temporary-tag%', '([^/]+)' );

		// Ensure the temporary tag now exists.
		$this->assertContains( 'temporary-tag=', $wp_rewrite->queryreplace );
		$this->assertContains( '%temporary-tag%', $wp_rewrite->rewritecode );

		// Ensure that the query variable is added to $wp->public_query_vars.
		$this->assertContains( 'temporary-tag', $wp->public_query_vars );
	}

	/**
	 * Ensure that sitemap providers registered in one test are NOT preserved in
	 * another.
	 *
	 * @dataProvider dataprovider_twice
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_sitemap_providers_preserved_between_tests(): void {
		$server = wp_sitemaps_get_server();

		$providers = $server->registry->get_providers();

		$this->assertArrayNotHasKey( 'test-provider', $providers );

		$provider = new class extends \WP_Sitemaps_Posts {};

		// Register a new sitemap provider for this test.
		wp_register_sitemap_provider( 'test-provider', new $provider );

		$providers = $server->registry->get_providers();

		$this->assertArrayHasKey( 'test-provider', $providers );
	}

	/**
	 * Test that a single template_redirect hook is registered for sitemaps. Previously, this was hooked more than
	 * once when testing.
	 *
	 * @dataProvider dataprovider_twice
	 */
	#[DataProvider( 'dataprovider_twice' )]
	public function test_sitemap_hooked_once_to_template_redirect(): void {
		$this->assertNotEmpty( $GLOBALS['wp_sitemaps'] ?? null, 'Expected $wp_sitemaps global to be set on load and backed up by PreserveGlobals.' );

		$server = wp_sitemaps_get_server();

		if ( ! isset( $GLOBALS['wp_filter']['template_redirect'] ) ) {
			$this->fail( 'No template_redirect hooks found.' );
		}

		$hooked = $GLOBALS['wp_filter']['template_redirect']->callbacks[10] ?? [];

		if ( empty( $hooked ) ) {
			$this->fail( 'No template_redirect hooks found at priority 10.' );
		}

		$sitemap_provider_hooks = collect( $hooked )
			->filter( fn ( $hook ) => is_array( $hook['function'] ) && isset( $hook['function'][0] ) && \WP_Sitemaps::class === $hook['function'][0]::class )
			->all();

		$this->assertCount(
			1,
			$sitemap_provider_hooks,
			'Expected exactly one template_redirect hook for WP_Sitemaps.',
		);

		$this->assertEquals(
			$server,
			collect( $sitemap_provider_hooks )->first()['function'][0],
			'Expected the hooked WP_Sitemaps instance to match the server instance.',
		);
	}

	/**
	 * Data provider that can be used to test that a data provider can be called
	 * multiple times without issue.
	 */
	public static function dataprovider_twice(): array {
		return [
			'first'  => [],
			'second' => [],
		];
	}
}
