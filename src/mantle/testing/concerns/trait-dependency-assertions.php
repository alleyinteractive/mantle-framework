<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * Dependency_Assertions trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;
use Mantle\Support\Collection;

trait Dependency_Assertions {

	public static function assertDependenciesLoaded( array $dependencies ) {
		if( count( $dependencies ) === 0 ) {
			PHPUnit::markTestIncomplete( 'Asserting an empty dependency array has been loaded does not assert that no dependencies have been loaded.' );
		}

		foreach( $dependencies as $dependency ) {
			if ( is_array( $dependency ) ) {
				$dependency = array_shift( $dependency );
			}

			if ( ! is_string( $dependency ) ) {
				PHPUnit::fail( 'Dependency type not string.' );
				continue;
			}

			self::assertDependencyLoaded( $dependency );
		}
	}

	public static function assertDependencyLoaded( string $dependency ) {
		static $includes;

		if (
			! is_array( $includes ) ||
			empty( $includes )
		) {
			$includes = new Collection( get_included_files() );
		}

		PHPUnit::assertTrue(
			$includes
				->filter(fn( $file ) => strpos( $file, $dependency ) !== false)
				->count() > 0,
			sprintf(
				'%s dependency not found in included files.',
				(string) $dependency
			)
		);
	}

}
