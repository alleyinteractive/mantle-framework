<?php
/**
 * Preserves_Globals trait file
 *
 * @package mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Attributes\DisableGlobalPreservation;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Trait to preserve certain WordPress global variables between tests.
 *
 * Ensures that object meta, post types, taxonomies, and post statuses remain
 * consistent across test runs. A backup is made before all tests run and is
 * restored before each test. A test's setUpBeforeClass() and setUp() methods
 * can safely modify these globals without affecting other tests. Individual
 * tests can disable global preservation by using the DisableGlobalPreservation
 * attribute.
 *
 * Previously, this was written using PHPUnit hooks, but has been updated to be
 * called directly from the TestCase class to improve compatibility with
 * PHPUnit 10. Once PHPUnit 11+ is the minimum supported version, the methods below
 * can be updated to use BeforeClass, Before, and AfterClass attributes.
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Preserves_Globals {
	use Reads_Annotations;

	/**
	 * Global variables to back up.
	 *
	 * @var string[]
	 */
	protected const GLOBALS_TO_BACKUP = [
		'wp_meta_keys',
		'wp_post_statuses',
		'wp_post_types',
		'wp_taxonomies',
	];

	/**
	 * Backup of the original global variables.
	 *
	 * @var array<string, mixed>|null
	 */
	protected static ?array $original_globals = null;

	/**
	 * Backup of the original public query variables.
	 *
	 * @var array<string>|null
	 */
	protected static ?array $original_wp_query_vars = null;

	/**
	 * Backup of the current test class' global variables.
	 *
	 * @var array<string, mixed>|null
	 */
	protected static ?array $current_class_globals = null;

	/**
	 * Backup of the current test class' public query variables.
	 *
	 * @var array<string>|null
	 */
	protected static ?array $current_class_wp_query_vars = null;

	/**
	 * Backup the original globals. This will create a backup of the original
	 * global variables before any tests run.
	 *
	 * In the future, this can be converted to use the BeforeClass attribute with
	 * a high priority.
	 */
	public static function backup_original_wordpress_globals(): void {
		global $wp;

		// Backup the original globals only once and reuse it for all test classes.
		if ( ! isset( self::$original_globals ) ) {
			foreach ( self::GLOBALS_TO_BACKUP as $global ) {
				self::$original_globals[ $global ] = $GLOBALS[ $global ];
			}
		} else {
			foreach ( self::GLOBALS_TO_BACKUP as $global ) {
				$GLOBALS[ $global ] = self::$original_globals[ $global ]; // phpcs:ignore
			}
		}

		// Backup the WordPress public query variables.
		self::$original_wp_query_vars = $wp->public_query_vars;

		self::$current_class_globals = null;
	}

	/**
	 * Store the globals after the test's setUpBeforeClass() runs.
	 *
	 * This will take a snapshot of the global variables after the test class's
	 * setUpBeforeClass() method has run. This will be used to restore the globals
	 * before each test.
	 *
	 * In the future, this can be converted to use the BeforeClass attribute with
	 * a low priority so it runs after other BeforeClass methods.
	 */
	public static function backup_current_class_wordpress_globals(): void {
		global $wp;

		self::$current_class_globals = [];

		foreach ( self::GLOBALS_TO_BACKUP as $global ) {
			self::$current_class_globals[ $global ] = $GLOBALS[ $global ];
		}

		// Backup the WordPress public query variables.
		self::$current_class_wp_query_vars = $wp->public_query_vars;
	}

	/**
	 * Restore the globals before each test.
	 *
	 * In the future, this can be converted to use the Before attribute with a
	 * high priority so it runs before other Before methods.
	 */
	public function restore_globals_before_each_test(): void {
		global $wp;

		if ( ! $this->is_global_preservation_supported_for_test() ) {
			return;
		}

		if ( ! isset( self::$current_class_globals ) ) {
			return;
		}

		foreach ( self::GLOBALS_TO_BACKUP as $global ) {
			// Skip restoring the meta keys global if the Unregister_All_Meta_Keys trait is used.
			if ( 'wp_meta_keys' === $global && self::usesTrait( Unregister_All_Meta_Keys::class ) ) {
				continue;
			}

			$GLOBALS[ $global ] = self::$current_class_globals[ $global ]; // phpcs:ignore
		}

		// Restore the WordPress public query variables.
		if ( self::$current_class_wp_query_vars ) {
			$wp->public_query_vars = self::$current_class_wp_query_vars;

		}
	}

	/**
	 * Restore the original globals after all tests have run in the class.
	 *
	 * In the future, this can be converted to use the AfterClass attribute with a
	 * low priority so it runs after other AfterClass methods.
	 */
	public static function restore_globals_after_all_tests(): void {
		global $wp;

		if ( ! isset( self::$original_globals ) ) {
			return;
		}

		foreach ( self::GLOBALS_TO_BACKUP as $global ) {
			$GLOBALS[ $global ] = self::$original_globals[ $global ]; // phpcs:ignore
		}

		// Restore the WordPress public query variables.
		if ( self::$original_wp_query_vars ) {
			$wp->public_query_vars = self::$original_wp_query_vars;
		}
	}

	/**
	 * Determine if global preservation is enabled for the current test.
	 */
	private function is_global_preservation_supported_for_test(): bool {
		return empty( $this->get_attributes_for_method( DisableGlobalPreservation::class ) );
	}
}
