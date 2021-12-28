<?php
/**
 * Discover_Events class file
 *
 * @package Mantle
 */

namespace Mantle\Framework\Events;

use Mantle\Support\Reflector;
use Mantle\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function Mantle\Framework\Helpers\collect;

/**
 * Discover events within a specific directory.
 */
class Discover_Events {
	/**
	 * Get all of the events and listeners by searching the given listener directory.
	 *
	 * @param string $path Listener path.
	 * @param string $base_path Base path of the application.
	 * @return array
	 */
	public static function within( string $path, string $base_path ): array {
		$listeners = collect(
			static::get_listener_events(
				( new Finder() )->files()->in( $path )->name( '*.php' ),
				$base_path,
			),
		);

		$discovered_events = [];

		foreach ( $listeners as $listener => $events ) {
			foreach ( $events as $event ) {
				if ( ! isset( $discovered_events[ $event ] ) ) {
					$discovered_events[ $event ] = [];
				}

				$discovered_events[ $event ][] = $listener;
			}
		}

		return $discovered_events;
	}

	/**
	 * Get all of the listeners and their corresponding events.
	 *
	 * @param iterable $listeners Listener files.
	 * @param string   $base_path Base path.
	 * @return array
	 */
	protected static function get_listener_events( $listeners, string $base_path ): array {
		$listener_events = [];

		foreach ( $listeners as $listener ) {
			try {
				$listener = new ReflectionClass(
					static::class_from_file( $listener, $base_path ),
				);
			} catch ( ReflectionException $e ) {
				continue;
			}

			if ( ! $listener->isInstantiable() ) {
				continue;
			}

			foreach ( $listener->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
				if ( ! Str::is( 'handle*', $method->name ) || ! isset( $method->getParameters()[0] ) ) {
					continue;
				}

				$listener_events[ $listener->name . '@' . $method->name ] = Reflector::get_paramater_class_names(
					$method->getParameters()[0]
				);
			}
		}

		return array_filter( $listener_events );
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

		return str_replace(
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
	}
}
