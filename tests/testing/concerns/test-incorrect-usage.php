<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Framework_Test_Case;

/**
 * Test for incorrect usage errors being thrown and handled.
 */
class Test_Incorrect_Usage extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();
	}

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
		$this->setExpectedIncorrectUsage( 'expected_incorrect_usage' );

		_doing_it_wrong( 'wp_prefix_test', 'This is a test', '1.0.0' );
		_doing_it_wrong( 'expected_incorrect_usage', 'This is a test', '1.0.0' );
	}

	public function test_ignore_any_incorrect_usage() {
		$this->ignoreIncorrectUsage();

		_doing_it_wrong( 'ignored_incorrect_usage', 'This is a test', '1.0.0' );
		_doing_it_wrong( 'expected_incorrect_usage', 'This is a test', '1.0.0' );
	}
}
