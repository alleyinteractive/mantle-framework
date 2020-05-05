<?php
/**
 * Bootstrapable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts;

use Mantle\Framework\Application;

/**
 * Bootstrapable Contract
 */
interface Bootstrapable {
	/**
	 * BOotstrap method.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app );
}
