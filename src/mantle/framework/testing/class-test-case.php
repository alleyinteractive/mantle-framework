<?php
/**
 * This file contains the Test_Case class.
 *
 * @package Mantle
 */

namespace Mantle\Foundation\Testing;

use Mantle\Framekwork\Testing\Concerns\Makes_Http_Requests;
use WP_UnitTestCase;

/**
 * Root Test Case for Mantle sites.
 */
abstract class Test_Case extends WP_UnitTestCase {
	use Makes_Http_Requests;
}
