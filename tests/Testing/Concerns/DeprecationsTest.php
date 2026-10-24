<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Attributes\Expected_Deprecation;
use Mantle\Testing\Attributes\Ignore_Deprecation;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Runner\Version;

/**
 * Test for deprecation errors being thrown and handled.
 *
 * @group testing
 */
#[Group( 'testing' )]
class DeprecationsTest extends FrameworkTestCase {
	/**
	 * @expectedDeprecated test_deprecation_annotation
	 */
	public function test_deprecation_with_annotation(): void {
		if ( class_exists( Version::class ) && version_compare( Version::id(), '12.0.0', '>=' ) ) {
			$this->markTestSkipped( 'PHPUnit 12.0.0+ does not support annotations.' );
		}

		_deprecated_function( 'test_deprecation_annotation', '1.0.0', 'test_deprecation_with_annotation' );
	}

	public function test_deprecation_within_test(): void {
		$this->setExpectedDeprecated( 'set_expected_within' );

		_deprecated_function( 'set_expected_within', '1.0.0', 'test_deprecation_within_test' );
	}

	#[Expected_Deprecation( 'expected_deprecation' )]
	public function test_deprecation_expected_attribute(): void {
		_deprecated_function( 'expected_deprecation', '1.0.0', 'test_deprecation_expected_attribute' );
	}

	public function test_ignore_specific_deprecation(): void {
		$this->ignoreDeprecated( 'ignored_deprecation' );
		$this->setExpectedDeprecated( 'expected_deprecation' );

		_deprecated_function( 'ignored_deprecation', '1.0.0', 'test_ignore_specific_deprecation' );
		_deprecated_function( 'expected_deprecation', '1.0.0', 'test_ignore_specific_deprecation' );
	}

	#[Ignore_Deprecation]
	public function test_ignore_all_deprecation_attribute(): void {
		_deprecated_function( 'ignored_deprecation', '1.0.0', 'test_ignore_specific_deprecation_attribute' );

		$this->assertTrue( true );
	}

	#[Ignore_Deprecation( 'ignored_deprecation' )]
	public function test_ignore_specific_deprecation_attribute(): void {
		_deprecated_function( 'ignored_deprecation', '1.0.0', 'test_ignore_specific_deprecation_attribute' );

		$this->assertTrue( true );
	}

	public function test_ignore_by_prefix(): void {
		$this->ignoreDeprecated( 'wp_*' );
		$this->setExpectedDeprecated( 'expected_deprecation' );

		_deprecated_function( 'wp_prefix_test', '1.0.0', 'test_ignore_by_prefix' );
		_deprecated_function( 'expected_deprecation', '1.0.0', 'test_ignore_by_prefix' );
	}

	public function test_ignore_any_deprecation(): void {
		$this->ignoreDeprecated();

		_deprecated_function( 'ignored_deprecation', '1.0.0', 'test_ignore_any_deprecation' );
		_deprecated_function( 'expected_deprecation', '1.0.0', 'test_ignore_any_deprecation' );

		$this->assertTrue( true );
	}
}
