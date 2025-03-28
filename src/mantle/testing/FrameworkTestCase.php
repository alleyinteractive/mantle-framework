<?php
/**
 * FrameworkTestCase class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Testing\Concerns\Create_Application;

/**
 * Test case for use inside of the framework. For external use, please use
 * {@see Mantle\Testkit\TestCase}.
 *
 * @access private
 */
abstract class FrameworkTestCase extends Test_Case {
	use Create_Application;
}
