<?php
/**
 * EarlyIncorrectUsageHandler class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing;

use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;

use function Mantle\Support\Helpers\collect;

/**
 * Handler for early _doing_it_wrong() calls early in the testing lifecycle
 * (during the bootstrap process).
 *
 * Within the context of a test, the \Mantle\Testing\Concerns\Incorrect_Usage
 * trait handles _doing_it_wrong() calls appropriately. However, if
 * _doing_it_wrong() is called before the test begins (for example, during the
 * bootstrap process), those calls are routed to this handler instead.
 *
 * @see \Mantle\Testing\Concerns\Incorrect_Usage
 *
 * @internal
 */
final class EarlyIncorrectUsageHandler {
	/**
	 * Register the hooks for the class.
	 */
	public static function register(): void {
		tests_add_filter( 'doing_it_wrong_run', [ self::class, 'handle_doing_it_wrong_run' ], 10, 2 );
		tests_add_filter( 'doing_it_wrong_trigger_error', '__return_false', 9 );
	}

	/**
	 * Deregister the hooks for the class.
	 */
	public static function unregister(): void {
		remove_action( 'doing_it_wrong_run', [ self::class, 'handle_doing_it_wrong_run' ] );
		remove_filter( 'doing_it_wrong_trigger_error', '__return_false', 9 );
	}

	/**
	 * Handle a _doing_it_wrong() call.
	 *
	 * @param string $function The function called incorrectly.
	 * @param string $message  The message for the incorrect usage.
	 */
	public static function handle_doing_it_wrong_run( string $function, string $message ): void {
		$writer = new TraceWriter(
			title: 'Unexpected incorrect usage call in test bootstrap',
			description: $message,
			frames: collect( Backtrace::create()->frames() )
				->skip_until( fn ( Frame $frame ) => $frame->method === '_doing_it_wrong' )
				->slice( 1 )
				->values()
				->all(),
			prefix: 'Early Incorrect Usage',
		);

		$writer->write();
	}
}
