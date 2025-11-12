<?php
/**
 * Preserves_Globals trait file
 *
 * @package mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Attributes\DisableGlobalPreservation;
use Mantle\Testing\Utils;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Trait to preserve certain WordPress global variables between tests.
 *
 * Ensures that object meta, post types, taxonomies, and post statuses remain
 * consistent across test runs. A backup is made before all tests run and is
 * restored before each test. A test's setUpBeforeClass() and setUp() methods
 * can safely modify these globals without affecting other tests. Individual tests
 * can disable global preservation by using the DisableGlobalPreservation attribute.
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
	private const GLOBALS_TO_BACKUP = [
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
	private static ?array $original_globals = null;

	/**
	 * Backup of the current class's global variables.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $current_class_globals = null;

	/**
	 * Backup the original globals.
	 * This will create a backup of the original global variables before any tests run.
	 *
	 * Intentionally using a high priority to ensure this runs before other setUpBeforeClass() methods.
	 */
	#[BeforeClass( 99999 )]
	public static function backup_original_wordpress_globals(): void {
		$backed_up_globals = false;

		// Always attempt to back up the original globals, regardless if
		// preservation is supported.
		if ( ! isset( self::$original_globals ) ) {
			foreach ( self::GLOBALS_TO_BACKUP as $global ) {
				self::$original_globals[ $global ] = $GLOBALS[ $global ];
			}

			$backed_up_globals = true;
		}

		if ( ! self::is_global_preservation_supported() ) {
			Utils::info( 'Global preservation is not supported in this PHPUnit version.', 'Preserves_Globals' );
			return;
		}

		if ( ! $backed_up_globals ) {
			foreach ( self::GLOBALS_TO_BACKUP as $global ) {
				$GLOBALS[ $global ] = self::$original_globals[ $global ]; // phpcs:ignore
			}
		}

		self::$current_class_globals = null;
	}

	/**
	 * Store the globals after the test's setUpBeforeClass() runs.
	 *
	 * This will take a snapshot of the global variables after the test class's
	 * setUpBeforeClass() method has run. This will be used to restore the globals
	 * before each test.
	 */
	#[BeforeClass( -99999 )]
	public static function backup_current_class_wordpress_globals(): void {
		if ( ! self::is_global_preservation_supported() ) {
			return;
		}

		self::$current_class_globals = [];

		foreach ( self::GLOBALS_TO_BACKUP as $global ) {
			self::$current_class_globals[ $global ] = $GLOBALS[ $global ];
		}
	}

	/**
	 * Restore the globals before each test.
	 *
	 * Intentionally using a high priority to ensure this runs before other Before
	 * methods. An individual test method can disable global preservation by
	 * using the DisableGlobalPreservation attribute.
	 */
	#[Before( 99999 )]
	public function restore_globals_before_each_test(): void {
		if ( ! self::is_global_preservation_supported() || ! $this->is_global_preservation_supported_for_test() ) {
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
	}

	/**
	 * Restore the original globals after all tests have run in the class.
	 */
	#[AfterClass( -99999 )]
	public static function restore_globals_after_all_tests(): void {
		if ( ! self::is_global_preservation_supported() || ! isset( self::$original_globals ) ) {
			return;
		}

		foreach ( self::GLOBALS_TO_BACKUP as $global ) {
			$GLOBALS[ $global ] = self::$original_globals[ $global ]; // phpcs:ignore
		}
	}

	/**
	 * Determine if global preservation is supported in the current PHPUnit version.
	 *
	 * Requires PHPUnit 10.0.0 or greater.
	 */
	private static function is_global_preservation_supported(): bool {
		return static::phpunit_version_compare( '10.0.0', '>=' );
	}

	/**
	 * Determine if global preservation is enabled for the current test.
	 */
	private function is_global_preservation_supported_for_test(): bool {
		if ( ! self::is_global_preservation_supported() ) {
			return false;
		}

		return empty( $this->get_attributes_for_method( DisableGlobalPreservation::class ) );
	}
}
