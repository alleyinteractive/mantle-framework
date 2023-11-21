<?php
/**
 * Example_Overload trait file
 *
 * @package Mantle
 */

namespace Mantle\Tests\Testkit\Concerns;

/**
 * Validates that method overloading is working as expected.
 */
trait ExampleOverload {

	/**
	 * @var array<string> Contains the names of the methods that have been overloaded.
	 */
	protected static array $overloaded_methods = [];

	/**
	 * This method should be run during the setUpBeforeClass method of
	 * the Test_Case.
	 */
	public static function exampleoverload_set_up_before_class(): void {
		static::$overloaded_methods[] = 'setUpBeforeClass';
	}
}
