<?php
/**
 * Discover_Blocks class file
 *
 * @package Mantle
 */

namespace Mantle\Blocks;

use Mantle\Contracts\Block;
use Mantle\Support\Str;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\collect;

/**
 * Discover blocks within a specific directory.
 */
class Discover_Blocks {
	/**
	 * Get all of the blocks by searching the given block directory.
	 *
	 * @param string $path Block path.
	 * @param string $base_path Base path of the application.
	 * @return array
	 */
	public static function within( string $path, string $base_path ): array {
		return collect(
			static::get_blocks(
				( new Finder() )->files()->in( $path )->name( '*.php' ),
				$base_path,
			),
		)->all();
	}

	/**
	 * Get all of the listeners and their corresponding events.
	 *
	 * @param iterable $blocks Listener files.
	 * @param string   $base_path Base path.
	 * @return array
	 */
	protected static function get_blocks( $blocks, string $base_path ): array {
		$found_blocks = [];

		foreach ( $blocks as $block ) {
			try {
				$block = new ReflectionClass(
					static::class_from_file( $block, $base_path ),
				);
			} catch ( ReflectionException $e ) {
				continue;
			}

			if ( ! $block->isInstantiable() ) {
				continue;
			}

			if ( ! $block->implementsInterface( Block::class ) ) {
				continue;
			}

			$found_blocks[] = $block->getName();
		}

		return array_filter( $found_blocks );
	}

	/**
	 * Extract the class name from a given file path.
	 *
	 * @param SplFileInfo $file File.
	 * @param string      $base_path Base path.
	 * @return string
	 */
	protected static function class_from_file( SplFileInfo $file, string $base_path ): string {
		$class = trim(
			Str::studly_underscore(
				Str::replace_last( 'class-', '', Str::replace_first( $base_path, '', $file->getRealPath() ) )
			),
			DIRECTORY_SEPARATOR,
		);

		$classname = str_replace(
			[
				DIRECTORY_SEPARATOR,
				ucfirst( basename( app()->get_app_path() ) ) . '\\',
			],
			[
				'\\',
				app()->get_namespace() . '\\',
			],
			ucfirst( Str::replace_last( '.php', '', $class ) ),
		);

		return $classname;
	}
}
