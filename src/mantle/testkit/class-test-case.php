<?php
/**
 * Test_Case class file
 *
 * @package Mantle
 */

namespace Mantle\Testkit;

use Mantle\Testkit\Concerns\Create_Application;
use Mantle\Testing\Test_Case as Testing_Test_Case;

/**
 * Testkit Test Case
 *
 * For use of the Mantle testing framework independent of the Mantle framework.
 * Inspired by `Orchestra\Testbench`.
 */
abstract class Test_Case extends Testing_Test_Case {
	use Create_Application;
}
