<?php
/**
 * This file contains the Deprecations Trait
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Support\Str;
use Mantle\Testing\Attributes\Expected_Deprecation;
use Mantle\Testing\Attributes\Ignore_Deprecation;
use Mantle\Testing\EarlyDeprecationsHandler;
use Mantle\Testing\Exceptions\UnexpectedDeprecatedNoticeException;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;

use function Mantle\Support\Helpers\collect;

trait Deprecations {
	use Output_Messages;
	use Reads_Annotations;

	/**
	 * WordPress deprecation types.
	 *
	 * @var string[]
	 */
	public const DEPRECATION_TYPES = [
		'argument',
		'class',
		'constructor',
		'file',
		'function',
		'hook',
	];

	/**
	 * Expected deprecation calls.
	 *
	 * @var array<string>
	 */
	private $expected_deprecated = [];

	/**
	 * Ignored deprecation calls.
	 *
	 * @var string[]
	 */
	private $ignored_deprecated = [];

	/**
	 * Caught deprecated calls.
	 *
	 * @var array<string>
	 */
	private $caught_deprecated = [];

	/**
	 * Trace storage for deprecated calls.
	 *
	 * @var array<array<Frame>>
	 */
	private array $caught_deprecated_traces = [];

	/**
	 * Sets up the expectations for testing a deprecated call.
	 */
	public function deprecations_set_up(): void {
		$this->register_listeners_for_deprecations();

		$annotations = $this->get_annotations_for_method();

		foreach ( [ 'class', 'method' ] as $depth ) {
			if ( ! empty( $annotations[ $depth ]['expectedDeprecated'] ) ) {
				$this->expected_deprecated = array_merge( $this->expected_deprecated, $annotations[ $depth ]['expectedDeprecated'] );
			}
		}

		// Allow attributes to define the expected and ignored deprecations.
		foreach ( $this->get_attributes_for_method( Expected_Deprecation::class ) as $attribute ) {
			$this->setExpectedDeprecated( $attribute->newInstance()->deprecation );
		}

		foreach ( $this->get_attributes_for_method( Ignore_Deprecation::class ) as $attribute ) {
			$this->ignoreDeprecated( $attribute->newInstance()->deprecation );
		}
	}

	/**
	 * Register the listeners for deprecated calls.
	 */
	private function register_listeners_for_deprecations(): void {
		EarlyDeprecationsHandler::unregister();

		foreach ( self::DEPRECATION_TYPES as $type ) {
			add_action( "deprecated_{$type}_run", [ $this, 'deprecated_run' ] );
			add_filter( "deprecated_{$type}_trigger_error", '__return_false', 9 );
		}

		// Filter for _deprecated_file() which doesn't follow the same pattern.
		add_action( 'deprecated_file_included', [ $this, 'deprecated_run' ] );
	}

	/**
	 * Handles a deprecated expectation.
	 *
	 * The DocBlock should contain `@expectedDeprecated` to trigger this.
	 *
	 * @throws \RuntimeException If the trace for a caught deprecated call is missing.
	 * @throws UnexpectedDeprecatedNoticeException If an unexpected deprecation is caught.
	 */
	public function deprecations_tear_down(): void {
		if ( empty( $this->expected_deprecated ) && empty( $this->caught_deprecated ) ) {
			return;
		}

		$errors = [];

		$not_caught_deprecated = array_diff( $this->expected_deprecated, $this->caught_deprecated );
		foreach ( $not_caught_deprecated as $not_caught ) {
			$errors[] = "Failed to assert that {$not_caught} triggered a deprecated notice";
		}

		$unexpected_deprecated = collect( $this->caught_deprecated )->filter(
			function ( string $caught ): bool {
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
		)->all();

		foreach ( $unexpected_deprecated as $index => $unexpected ) {
			if ( ! isset( $this->caught_deprecated_traces[ $index ] ) ) {
				throw new \RuntimeException( 'Trace for caught deprecated call is missing.' );
			}

			throw UnexpectedDeprecatedNoticeException::create(
				message: "Unexpected deprecated notice for {$unexpected}",
				frame: collect( $this->caught_deprecated_traces[ $index ] )
					->skip_until( fn ( Frame $frame ): bool => in_array(
						$frame->method,
						self::get_deprecation_methods(),
						true,
					) )
					->slice( 1 )
					->first_or_fail(),
			);
		}

		if ( ! empty( $errors ) ) {
			$this->fail( 'Unexpected deprecated notices: ' . implode( ', ', $errors ) );
		}

		if ( ! empty( $this->expected_deprecated ) ) {
			$this->addToAssertionCount( count( $this->expected_deprecated ) );
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
	public function setExpectedDeprecated( string $deprecated ): void {
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
	public function ignoreDeprecated( string $deprecated = '*' ): void {
		$this->ignored_deprecated[] = $deprecated;
	}

	/**
	 * Adds a deprecated call to the list of caught deprecated calls.
	 *
	 * @param string $name The name of the deprecated argument/function/hook/etc.
	 */
	public function deprecated_run( string $name ): void {
		if ( ! in_array( $name, $this->caught_deprecated, true ) ) {
			$this->caught_deprecated[] = $name;

			$this->caught_deprecated_traces[] = Backtrace::create()->frames();
		}
	}

	/**
	 * Get the deprecation method names.
	 *
	 * @return string[]
	 */
	public static function get_deprecation_methods(): array {
		return collect( self::DEPRECATION_TYPES )->map(
			fn ( string $type ): string => "_deprecated_{$type}"
		)->all();
	}
}
