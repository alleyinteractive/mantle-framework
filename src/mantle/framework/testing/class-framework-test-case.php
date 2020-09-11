<?php
/**
 * Framework_Test_Case class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing;

use Mantle\Framework\Testing\Concerns\Create_Application;

/**
 * Test case for use inside of the framework to automatically setup an application.
 * Inspired by `Orchestra\Testbench`.
 */
abstract class Framework_Test_Case extends Test_Case {
	use Create_Application;
}
