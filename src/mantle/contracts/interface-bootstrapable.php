<?php
/**
 * Bootstrapable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts;

use Mantle\Framework\Application;

/**
 * Bootstrapable Contract
 */
interface Bootstrapable {
	/**
	 * Bootstrap method.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app );
}
