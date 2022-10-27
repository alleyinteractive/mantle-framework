<?php
/**
 * This file contains the Deprecations Trait
 *
 * @package Mantle
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

namespace Mantle\Testing\Concerns;

use Mantle\Support\Str;
use PHPUnit\Util\Test;

use function Mantle\Support\Helpers\collect;

trait Deprecations {

	/**
	 * Expected deprecation calls.
	 *
	 * @var array
	 */
	protected $expected_deprecated = [];

	/**
	 * Ignored deprecation calls.
	 *
	 * @var string[]
	 */
	protected $ignored_deprecated = [];

	/**
	 * Caught deprecated calls.
	 *
	 * @var array
	 */
	protected $caught_deprecated = [];

	/**
	 * Sets up the expectations for testing a deprecated call.
	 */
	public function deprecations_set_up() {
		if ( ! method_exists( $this, 'getAnnotations' ) ) {
			$annotations = Test::parseTestMethodAnnotations(
				static::class,
				$this->getName()
			);
		} else {
			$annotations = $this->getAnnotations();
		}

		foreach ( [ 'class', 'method' ] as $depth ) {
			if ( ! empty( $annotations[ $depth ]['expectedDeprecated'] ) ) {
				$this->expected_deprecated = array_merge( $this->expected_deprecated, $annotations[ $depth ]['expectedDeprecated'] );
			}
		}

		add_action( 'deprecated_function_run', [ $this, 'deprecated_function_run' ] );
		add_action( 'deprecated_argument_run', [ $this, 'deprecated_function_run' ] );
		add_action( 'deprecated_hook_run', [ $this, 'deprecated_function_run' ] );
		add_action( 'deprecated_function_trigger_error', '__return_false' );
		add_action( 'deprecated_argument_trigger_error', '__return_false' );
		add_action( 'deprecated_hook_trigger_error', '__return_false' );
	}

	/**
	 * Handles a deprecated expectation.
	 *
	 * The DocBlock should contain `@expectedDeprecated` to trigger this.
	 */
	public function deprecations_tear_down() {
		$errors = [];

		$not_caught_deprecated = array_diff( $this->expected_deprecated, $this->caught_deprecated );
		foreach ( $not_caught_deprecated as $not_caught ) {
			$errors[] = "Failed to assert that $not_caught triggered a deprecated notice";
		}

		$unexpected_deprecated = collect( $this->caught_deprecated )
			->filter(
				function ( string $caught ) {
					$ignored_and_expected = array_merge( $this->expected_deprecated, $this->ignored_deprecated );

					if ( in_array( $caught, $ignored_and_expected, true ) ) {
						return false;
					}

					// Allow partial matches when ignoring a deprecation call.
					foreach ( $this->ignored_deprecated as $ignored ) {
						if ( Str::is( $ignored, $caught ) ) {
							return false;
						}
					}

					return true;
				}
			)
			->all();

		foreach ( $unexpected_deprecated as $unexpected ) {
			$errors[] = "Unexpected deprecated notice for $unexpected";
		}

		// Perform an assertion, but only if there are expected or unexpected deprecated calls or wrongdoings.
		if (
			! empty( $this->expected_deprecated )
			|| ! empty( $this->caught_deprecated )
		) {
			$this->assertEmpty( $errors, implode( "\n", $errors ) );
		}
	}

	/**
	 * Declare an expected `_deprecated_function()` or `_deprecated_argument()`
	 * call from within a test.
	 *
	 * Note: If a deprecation call isn't made within the test, the test will fail.
	 * To ignore the deprecation entirely, use {@see Deprecations::setExpectedDeprecated()}.
	 *
	 * @param string $deprecated Name of the function, method, class, or argument
	 *                           that is deprecated. Must match the first
	 *                           parameter of the `_deprecated_function()` or
	 *                           `_deprecated_argument()` call.
	 */
	public function setExpectedDeprecated( $deprecated ) {
		$this->expected_deprecated[] = $deprecated;
	}

	/**
	 * Ignore a deprecation call from within a test.
	 *
	 * Supports partial matches using `Str::is()` syntax with * as a wildcard.
	 *
	 * @param string $deprecated Name of the function, method, class, or argument
	 *                           that is deprecated. Must match the first
	 *                           parameter of the `_deprecated_function()` or
	 *                           `_deprecated_argument()` call.
	 */
	public function ignoreDeprecated( $deprecated = '*' ) {
		$this->ignored_deprecated[] = $deprecated;
	}

	/**
	 * Adds a deprecated function to the list of caught deprecated calls.
	 *
	 * @param string $function The deprecated function.
	 */
	public function deprecated_function_run( $function ) {
		if ( ! in_array( $function, $this->caught_deprecated, true ) ) {
			$this->caught_deprecated[] = $function;
		}
	}
}
