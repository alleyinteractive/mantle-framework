<?php
/**
 * This file contains the Assert class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\RegularExpression;

/**
 * Internal assertions that exist in PHPUnit 8.x.
 * Once the code base is migrated to use PHPUnit 8+ we can remove this file.
 *
 * @internal This class is not meant to be used or overwritten outside the framework itself.
 */
abstract class Assert extends PHPUnit {

	/**
	 * Asserts that a file does not exist.
	 *
	 * @param  string $filename
	 * @param  string $message
	 * @return void
	 */
	public static function assertFileDoesNotExist( string $filename, string $message = '' ): void {
		static::assertFalse( is_file( $filename ), $message );
	}

	/**
	 * Asserts that a directory does not exist.
	 *
	 * @param  string $directory
	 * @param  string $message
	 * @return void
	 */
	public static function assertDirectoryDoesNotExist( string $directory, string $message = '' ): void {
		static::assertFalse( is_dir( $directory ), $message );
	}

	/**
	 * Asserts that a string matches a given regular expression.
	 *
	 * @param  string $pattern
	 * @param  string $string
	 * @param  string $message
	 * @return void
	 */
	public static function assertMatchesRegularExpression( string $pattern, string $string, string $message = '' ): void {
		static::assertThat( $string, new RegularExpression( $pattern ), $message );
	}
}
