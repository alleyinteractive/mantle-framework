<?php
/**
 * Kernel class file
 *
 * @package Mantle
 */

namespace Mantle\Featherkit\Http;

use Mantle\Framework\Http\Kernel as HttpKernel;

/**
 * Featherkit HTTP Kernel
 *
 * Removes the Mantle-specific need of loading CLI commands and configuration
 * from disk.
 */
class Kernel extends HttpKernel {
	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array
	 */
	protected $bootstrappers = [
		\Mantle\Framework\Bootstrap\Load_Environment_Variables::class,
		\Mantle\Framework\Bootstrap\Register_Facades::class,
		\Mantle\Framework\Bootstrap\Register_Providers::class,
		\Mantle\Framework\Bootstrap\Boot_Providers::class,
	];
}
