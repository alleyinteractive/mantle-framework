<?php
/**
 * Parallel_Testing Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Parallel_Testing Facade
 *
 * @method static void resolveOptionsUsing(\Closure|null $resolver)
 * @method static void resolveTokenUsing(\Closure|null $resolver)
 * @method static void setUpProcess(callable $callback)
 * @method static void setUpTestCase(callable $callback)
 * @method static void setUpTestDatabase(callable $callback)
 * @method static void tearDownProcess(callable $callback)
 * @method static void tearDownTestCase(callable $callback)
 * @method static void callSetUpProcessCallbacks()
 * @method static void callSetUpTestCaseCallbacks(void $testCase)
 * @method static void callSetUpTestDatabaseCallbacks(string $database)
 * @method static void callTearDownProcessCallbacks()
 * @method static void callTearDownTestCaseCallbacks(void $testCase)
 * @method static mixed option(string $option)
 * @method static string|false token()
 *
 * @see \Mantle\Testing\Parallel\Parallel_Testing
 */
class Parallel_Testing extends Facade {
	/**
	 * Facade Accessor
	 */
	protected static function get_facade_accessor(): string {
		return \Mantle\Testing\Parallel\Parallel_Testing::class;
	}
}
