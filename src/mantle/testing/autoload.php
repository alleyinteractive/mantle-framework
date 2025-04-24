<?php
/**
 * Autoloaded File to support Testing
 *
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Faker\Factory;
use Faker\Generator;
use Mantle\Container\Container;
use Mantle\Faker\Faker_Provider;
use Mantle\Support\HTML;
use PHPUnit\Framework\AssertionFailedError;

use function Mantle\Support\Helpers\tap;

require_once __DIR__ . '/preload.php';
require_once __DIR__ . '/mail/helpers.php';

/**
 * While we are in a transition state with using PSR-4 coding style in tests,
 * the actual mantle-framework/testing package is still written in
 * WordPress-style code. This doesn't sit well when using select class names in
 * PSR-4 code. For the time being, we will manually require the files.
 *
 * We cannot use Composer's PSR-4 autoloader because the folder names are lower
 * case and folder names are case sensitive on some file systems (e.g. Linux).
 */

require_once __DIR__ . '/attributes/Environment.php';
require_once __DIR__ . '/attributes/UserAgent.php';

/**
 * Retrieve an instance of the Installation Manager
 *
 * The manager can install the Mantle Testing Framework but will not by default.
 * Call {@see Installation_Manager::install()} to install or use the
 * {@see install()} helper.
 */
function manager(): Installation_Manager {
	return Installation_Manager::instance();
}

/**
 * Install the Mantle Testing Framework
 *
 * @param callable|null $callback Callback to invoke once the installation has begun.
 */
function install( ?callable $callback = null ): Installation_Manager {
	return tap(
		manager(),
		fn ( Installation_Manager $manager ) => $manager->before( $callback ),
	)->install();
}

/**
 * Create a new HTML_String instance.
 *
 * @param string $html The HTML string to test.
 */
function html_string( string $html ): HTML {
	return new HTML( $html );
}

/**
 * Create a new Mock HTTP Response
 *
 * @param string $body    Response body.
 * @param array  $headers Response headers.
 */
function mock_http_response( string $body = '', array $headers = [] ): Mock_Http_Response {
	return new Mock_Http_Response( $body, $headers );
}

/**
 * Create a new Mock HTTP Response Sequence
 */
function mock_http_sequence(): Mock_Http_Sequence {
	return new Mock_Http_Sequence();
}

/**
 * Create a new block factory instance.
 */
function block_factory(): Block_Factory {
	$container = Container::get_instance();

	// If the Generator is not bound to the container, bind it.
	if ( ! $container->bound( Generator::class ) ) {
		$container->singleton(
			Generator::class,
			fn () => tap(
				Factory::create(),
				fn ( Generator $generator ) => $generator->addProvider( new Faker_Provider( $generator ) ),
			),
		);
	}

	return $container->make( Block_Factory::class );
}

/**
 * Iterate a test a number of times, catching any assertion failures and
 * re-throwing them with the iteration number in the message.
 *
 * @throws AssertionFailedError Thrown when an assertion fails.
 *
 * @param \Closure $callback The callback to execute for each iteration.
 * @param int      $times The number of times to iterate (default: 3).
 */
function iterate_test( \Closure $callback, int $times = 3 ): void {
	for ( $i = 0; $i < $times; $i++ ) {
		try {
			$callback( $i + 1 );
		} catch ( AssertionFailedError $e ) {
			throw new AssertionFailedError(
				'Failed on iteration ' . $i . ': ' . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}
	}
}
