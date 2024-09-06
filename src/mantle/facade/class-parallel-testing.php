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
 * @method static void resolve_token_using(Closure|null $resolver)
 * @method static void set_up_process(Closure $callback)
 * @method static void set_up_test_case(Closure $callback)
 * @method static void set_up_test_database(Closure $callback)
 * @method static void tear_down_process(Closure $callback)
 * @method static void tear_down_test_case(Closure $callback)
 * @method static void call_set_up_process_callbacks()
 * @method static void call_set_up_test_case_callbacks(\Mantle\Testing\Test_Case $test_case)
 * @method static void call_set_up_test_database_callbacks(string $database)
 * @method static void call_tear_down_process_callbacks()
 * @method static void call_tear_down_test_case_callbacks(\Mantle\Testing\Test_Case $test_case)
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
