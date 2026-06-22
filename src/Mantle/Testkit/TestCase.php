<?php
/**
 * Test_Case class file
 *
 * @package Mantle
 */

namespace Mantle\Testkit;

use Mantle\Testkit\Concerns\Create_Application;
use Mantle\Testkit\Concerns\Installs_WordPress;
use Mantle\Testing\TestCase as TestingTestCase;

/**
 * Testkit Test Case
 *
 * For use of the Mantle testing framework independent of the Mantle framework.
 * Inspired by `Orchestra\Testbench`.
 */
abstract class TestCase extends TestingTestCase {
	use Create_Application;

	/**
	 * Add Testkit specific traits to Priority list.
	 */
	protected static function get_priority_traits(): array {
		$parent_priorities = parent::get_priority_traits();

		$priorities = [
			Installs_WordPress::class,
		];

		return array_merge( $priorities, $parent_priorities );
	}
}
