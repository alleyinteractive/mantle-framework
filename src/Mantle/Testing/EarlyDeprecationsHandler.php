<?php
/**
 * EarlyDeprecationsHandler class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing;

use Closure;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;

use function Mantle\Support\Helpers\collect;

/**
 * Handler for early deprecation calls early in the testing lifecycle
 *
 * @see \Mantle\Testing\Concerns\Deprecations
 *
 * @internal
 */
final class EarlyDeprecationsHandler {
	/**
	 * Hook priority for deprecation handlers.
	 *
	 * This priority is cleared fully when unregistering the hooks. The priority
	 * is assumed to not be commonly used by other handlers.
	 *
	 * @var int
	 */
	public const HOOK_PRIORITY = 57;

	/**
	 * Register the hooks for the class.
	 */
	public static function register(): void {
		foreach ( TestCase::DEPRECATION_TYPES as $type ) {
			tests_add_filter(
				"deprecated_{$type}_run",
				self::create_deprecated_run_callback( $type, self::deprecated_run( ... ) ),
				self::HOOK_PRIORITY,
				99,
			);

			tests_add_filter(
				"deprecated_{$type}_trigger_error",
				'__return_false',
				self::HOOK_PRIORITY,
			);
		}

		// Filter for _deprecated_file() which doesn't follow the same pattern.
		tests_add_filter(
			'deprecated_file_included',
			self::create_deprecated_run_callback( 'file', self::deprecated_run( ... ) ),
			self::HOOK_PRIORITY,
			199,
		);
	}

	/**
	 * Deregister the hooks for the class.
	 */
	public static function unregister(): void {
		foreach ( TestCase::DEPRECATION_TYPES as $type ) {
			remove_all_actions( "deprecated_{$type}_run", self::HOOK_PRIORITY );
			remove_filter( "deprecated_{$type}_trigger_error", '__return_false', self::HOOK_PRIORITY );
		}

		// Filters for _deprecated_file() which doesn't follow the same pattern.
		remove_all_actions( 'deprecated_file_included', self::HOOK_PRIORITY );
	}

	/**
	 * Handle a deprecated call.
	 *
	 * @param string      $type    The type of deprecation (function, argument, hook, etc).
	 * @param string      $name    The name of the deprecated argument/function/hook/etc.
	 * @param string|null $message An optional message.
	 */
	public static function deprecated_run( string $type, string $name, ?string $message = null ): void {
		if ( empty( $message ) ) {
			$message = "Deprecation notice for {$type} '{$name}'.";
		}

		$writer = new TraceWriter(
			title: "Unexpected {$type} deprecation notice in test bootstrap",
			description: $message,
			frames: collect( Backtrace::create()->frames() )
				->skip_until( fn ( Frame $frame ) => in_array( $frame->method, TestCase::get_deprecation_methods(), true ) )
				->slice( 1 )
				->values()
				->all(),
			prefix: 'Early Deprecation',
		);

		$writer->write();
	}

	/**
	 * Create a callback for deprecated method calls.
	 *
	 * This method will resolve the varying argument structures of the different
	 * deprecation types in WordPress and call the provided callback with a
	 * consistent set of parameters.
	 *
	 * @param string  $type The type of deprecation (function, argument, hook, etc).
	 * @param Closure $callback The callback to run on deprecation.
	 *
	 * @phpstan-param Closure(string $type, string $name, ?string $message): void $callback
	 */
	public static function create_deprecated_run_callback( string $type, Closure $callback ): callable {
		return function ( ...$args ) use ( $type, $callback ): void {
			// Because WordPress is WordPress, the position of the message parameter
			// varies based on the type of deprecation.
			$message = match ( $type ) {
				'function'    => $args[3] ?? null,
				'constructor' => $args[2] ?? null,
				'class'       => $args[2] ?? null,
				'file'        => $args[3] ?? null,
				'argument'    => $args[2] ?? null,
				'hook'        => $args[3] ?? null,
				default       => end( $args ) ?: null, // Fallback to the last argument.
			};

			$callback(
				type: $type,
				name: $args[0],
				message: $message,
			);
		};
	}
}
