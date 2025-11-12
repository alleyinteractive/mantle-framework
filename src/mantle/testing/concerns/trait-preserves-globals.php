<?php
/**
 * Preserves_Globals trait file
 *
 * @package mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Utils;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * @mixin \Mantle\Testing\TestCase
 */
trait Preserves_Globals {
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
	 * Backup of global variables.
	 *
	 * @var array<string, mixed>
	 */
	private static array $globals_backup = [];

	/**
	 * Backup the globals after all test setUpBeforeClass() hook methods.
	 *
	 * @beforeClass
	 */
	#[BeforeClass( -9999 )]
	public static function backup_globals_after_all_set_up_before_class(): void {
		if ( ! self::is_global_preservation_supported() ) {
			Utils::info( 'Global preservation is not supported in this PHPUnit version.', 'Preserves_Globals' );
			return;
		}

		// Backup global variables that may be modified during tests.
		if ( empty( self::$globals_backup ) ) {
			foreach ( self::GLOBALS_TO_BACKUP as $global ) {
				self::$globals_backup[ $global ] = $GLOBALS[ $global ];
			}
		}
	}

	/**
	 * Restore the globals before each test.
	 */
	#[Before( 9999 )]
	public function restore_globals_before_each_test(): void {
		foreach ( self::GLOBALS_TO_BACKUP as $global ) {
			$GLOBALS[ $global ] = self::$globals_backup[ $global ]; // phpcs:ignore
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
}
