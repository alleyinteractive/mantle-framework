<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\Attributes\DisableGlobalPreservation;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Support\Helpers\collect;

/**
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
