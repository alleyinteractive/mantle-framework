<?php
/**
 * An Example block class
 * @package Mantle
 * @subpackage Tests
 */

namespace Mantle\Tests\Framework\Blocks\Fixtures\Blocks;

use Mantle\Contracts\Block;

class Mock_Block implements Block {
	/**
	 * Number of times this block has been registered.
	 */
	public static int $registrations = 0;

	public function register() {
		self::$registrations++;
	}

	public function render() {}
}
