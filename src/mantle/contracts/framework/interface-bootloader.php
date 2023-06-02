<?php
/**
 * Bootloader interface file
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Framework;

use Closure;

/**
 * Bootloader Contract
 *
 * Used to instantiate the application and load the framework.
 */
interface Bootloader {
	/**
	 * Boot the application given the current context.
	 *
	 * @return static
	 */
	public function boot(): static;

	/**
	 * Bind to the container before booting.
	 *
	 * @param string              $abstract Abstract to bind.
	 * @param Closure|string|null $concrete Concrete to bind.
	 * @return static
	 */
	public function bind( string $abstract, Closure|string|null $concrete ): static;
}
