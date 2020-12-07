<?php
/**
 * This file contains the Incorrect_Usage Trait
 *
 * @package Mantle
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

namespace Mantle\Framework\Testing\Concerns;

use PHPUnit\Util\Test;

trait Incorrect_Usage {

	/**
	 * Expected "doing it wrong" calls.
	 *
	 * @var array
	 */
	protected $expected_doing_it_wrong = [];

	/**
	 * Caught "doing it wrong" calls.
	 *
	 * @var array
	 */
	protected $caught_doing_it_wrong = [];

	/**
	 * Sets up the expectations for testing a deprecated call.
	 */
	public function expectIncorrectUsage() {
		if ( ! method_exists( $this, 'getAnnotations' ) ) {
			$annotations = Test::parseTestMethodAnnotations(
				static::class,
				$this->name
			);
		} else {
			$annotations = $this->getAnnotations();
		}

		foreach ( [ 'class', 'method' ] as $depth ) {
			if ( ! empty( $annotations[ $depth ]['expectedIncorrectUsage'] ) ) {
				$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, $annotations[ $depth ]['expectedIncorrectUsage'] );
			}
		}
		add_action( 'doing_it_wrong_run', [ $this, 'doing_it_wrong_run' ] );
		add_action( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Handles a deprecated expectation.
	 *
	 * The DocBlock should contain `@expectedDeprecated` to trigger this.
	 */
	public function expectedIncorrectUsage() {
		$errors = [];

		$not_caught_doing_it_wrong = array_diff( $this->expected_doing_it_wrong, $this->caught_doing_it_wrong );
		foreach ( $not_caught_doing_it_wrong as $not_caught ) {
			$errors[] = "Failed to assert that $not_caught triggered an incorrect usage notice";
		}

		$unexpected_doing_it_wrong = array_diff( $this->caught_doing_it_wrong, $this->expected_doing_it_wrong );
		foreach ( $unexpected_doing_it_wrong as $unexpected ) {
			$errors[] = "Unexpected incorrect usage notice for $unexpected";
		}

		// Perform an assertion, but only if there are expected or unexpected deprecated calls or wrongdoings.
		if (
			! empty( $this->expected_doing_it_wrong )
			|| ! empty( $this->caught_doing_it_wrong )
		) {
			$this->assertEmpty( $errors, implode( "\n", $errors ) );
		}
	}

	/**
	 * Declare an expected `_doing_it_wrong()` call from within a test.
	 *
	 * @since 4.2.0
	 *
	 * @param string $doing_it_wrong Name of the function, method, or class that appears in the first argument
	 *                               of the source `_doing_it_wrong()` call.
	 */
	public function setExpectedIncorrectUsage( $doing_it_wrong ) {
		$this->expected_doing_it_wrong[] = $doing_it_wrong;
	}

	/**
	 * Adds a function called in a wrong way to the list of `_doing_it_wrong()` calls.
	 *
	 * @param string $function The function to add.
	 */
	public function doing_it_wrong_run( $function ) {
		if ( ! in_array( $function, $this->caught_doing_it_wrong, true ) ) {
			$this->caught_doing_it_wrong[] = $function;
		}
	}
}
