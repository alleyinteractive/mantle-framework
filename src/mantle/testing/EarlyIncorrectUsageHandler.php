<?php
declare(strict_types=1);

namespace Mantle\Testing;

/**
 * Handler for early _doing_it_wrong() calls in the framework.
 *
 * Within tests, \Mantle\Testing\Concerns\Incorrect_Usage handles _doing_it_wrong()
 * calls made during the test execution. However, if _doing_it_wrong() is called
 * before the test begins (for example, during the bootstrap process), those calls
 * need to be handled differently.
 */
class EarlyIncorrectUsageHandler {
	/**
	 * Register the hooks for the class.
	 */
	public static function register(): void {
		// Capture incorrect usage errors during tests.
		tests_add_filter( 'doing_it_wrong_run', [ __CLASS__, 'handle_doing_it_wrong_run' ], 10, 2 );

		// Prevent incorrect usage errors from throwing errors during tests. Errors will
		// be handled manually in tests.
		tests_add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Deregister the hooks for the class.
	 */
	public static function unregister(): void {
		remove_action( 'doing_it_wrong_run', [ __CLASS__, 'handle_doing_it_wrong_run' ] );
		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Handle a _doing_it_wrong() call.
	 *
	 * @param string $function The function called incorrectly.
	 * @param string $message  The message for the incorrect usage.
	 */
	public static function handle_doing_it_wrong_run( string $function, string $message ): void {
		$exception = new \ErrorException( "Incorrect usage notice for {$function}: {$message}" );

		$printer = new \NunoMaduro\Collision\Adapters\Phpunit\Printers\DefaultPrinter( true );

		$printer->report( $exception );
	}
}
