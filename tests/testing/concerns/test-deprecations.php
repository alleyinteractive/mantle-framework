<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Framework_Test_Case;

/**
 * Test for deprecation errors being thrown and handled.
 *
 * @group testing
 */
class Test_Deprecations extends Framework_Test_Case {
	/**
	 * @expectedDeprecated test_deprecation_annotation
	 */
	public function test_deprecation_with_annotation() {
		_deprecated_function( 'test_deprecation_annotation', '1.0.0', 'test_deprecation_with_annotation' );
	}

	public function test_deprecation_within_test() {
		$this->setExpectedDeprecated( 'set_expected_within' );

		_deprecated_function( 'set_expected_within', '1.0.0', 'test_deprecation_within_test' );
	}

	public function test_ignore_specific_deprecation() {
		$this->ignoreDeprecated( 'ignored_deprecation' );
		$this->setExpectedDeprecated( 'expected_deprecation' );

		_deprecated_function( 'ignored_deprecation', '1.0.0', 'test_ignore_specific_deprecation' );
		_deprecated_function( 'expected_deprecation', '1.0.0', 'test_ignore_specific_deprecation' );
	}

	public function test_ignore_by_prefix() {
		$this->ignoreDeprecated( 'wp_*' );
		$this->setExpectedDeprecated( 'expected_deprecation' );

		_deprecated_function( 'wp_prefix_test', '1.0.0', 'test_ignore_by_prefix' );
		_deprecated_function( 'expected_deprecation', '1.0.0', 'test_ignore_by_prefix' );
	}

	public function test_ignore_any_deprecation() {
		$this->ignoreDeprecated();

		_deprecated_function( 'ignored_deprecation', '1.0.0', 'test_ignore_any_deprecation' );
		_deprecated_function( 'expected_deprecation', '1.0.0', 'test_ignore_any_deprecation' );
	}
}
