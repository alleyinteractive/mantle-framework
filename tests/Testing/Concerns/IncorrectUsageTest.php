<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Attributes\Expected_Incorrect_Usage;
use Mantle\Testing\Attributes\Ignore_Incorrect_Usage;
use Mantle\Testing\Framework_Test_Case;

/**
 * Test for incorrect usage errors being thrown and handled.
 *
 * @group testing
 */
class IncorrectUsageTest extends Framework_Test_Case {
	/**
	 * @expectedIncorrectUsage test_incorrect_usage_annotation
	 */
	public function test_incorrect_usage_with_annotation() {
		_doing_it_wrong( 'test_incorrect_usage_annotation', 'This is a test', '1.0.0' );
	}

	public function test_incorrect_usage_within_test() {
		$this->setExpectedIncorrectUsage( 'set_expected_within' );

		_doing_it_wrong( 'set_expected_within', 'This is a test', '1.0.0' );
	}

	public function test_ignore_specific_incorrect_usage() {
		$this->ignoreIncorrectUsage( 'ignored_incorrect_usage' );
		$this->setExpectedIncorrectUsage( 'expected_incorrect_usage' );

		_doing_it_wrong( 'ignored_incorrect_usage', 'This is a test', '1.0.0' );
		_doing_it_wrong( 'expected_incorrect_usage', 'This is a test', '1.0.0' );
	}

	public function test_ignore_by_prefix() {
		$this->ignoreIncorrectUsage( 'wp_*' );
		$this->ignoreIncorrectUsage( 'WP_*' );
		$this->setExpectedIncorrectUsage( 'expected_incorrect_usage' );

		wp_cache_set( false, 'data' ); // Throws a deprecation notice because of the invalid cache key.
		_doing_it_wrong( 'wp_prefix_test', 'This is a test', '1.0.0' );
		_doing_it_wrong( 'expected_incorrect_usage', 'This is a test', '1.0.0' );
	}

	public function test_ignore_any_incorrect_usage() {
		$this->ignoreIncorrectUsage();

		_doing_it_wrong( 'ignored_incorrect_usage', 'This is a test', '1.0.0' );
		_doing_it_wrong( 'expected_incorrect_usage', 'This is a test', '1.0.0' );
	}

	#[Expected_Incorrect_Usage( 'expected_incorrect_usage' )]
	public function test_expected_by_attribute() {
		_doing_it_wrong( 'expected_incorrect_usage', 'This is a test', '1.0.0' );
	}

	#[Ignore_Incorrect_Usage]
	public function test_ignore_by_attribute() {
		_doing_it_wrong( 'ignored_incorrect_usage', 'This is a test', '1.0.0' );
	}

	#[Ignore_Incorrect_Usage( 'ignored_incorrect_usage' )]
	public function test_ignore_specific_by_attribute() {
		_doing_it_wrong( 'ignored_incorrect_usage', 'This is a test', '1.0.0' );
	}
}
