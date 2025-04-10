<?php
/**
 * Interacts_With_PHPUnit trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Runner\Version;

/**
 * Interactions with PHPUnit.
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Interacts_With_PHPUnit {
	/**
	 * Skip the test if the PHPUnit version matches the given version.
	 *
	 * Useful for skipping tests that are not compatible with a specific version of PHPUnit.
	 *
	 * @param string $version The version to compare against.
	 * @param string $compare The comparison operator (default: '>=').
	 * @param string $message The message to display when skipping the test.
	 */
	public function skip_for_phpunit_version( string $version, string $compare = '>=', string $message = '' ): void {
		if ( ! class_exists( Version::class ) || ! method_exists( Version::class, 'id' ) ) {
			$this->markTestSkipped( 'PHPUnit version check not available.' );
		}

		if ( version_compare( \PHPUnit\Runner\Version::id(), $version, $compare ) ) {
			$this->markTestSkipped( $message ?: "PHPUnit version {$version} not met." );
		}
	}

	/**
	 * Skip the test if the PHPUnit version is 12.0.0 or greater.
	 *
	 * @param string $message The message to display when skipping the test.
	 */
	public function skip_for_phpunit_12( string $message ): void {
		$this->skip_for_phpunit_version( '12.0.0', '>=', $message );
	}
}
