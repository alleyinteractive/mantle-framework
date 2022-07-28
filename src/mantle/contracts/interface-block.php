<?php
/**
 * Block interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts;

/**
 * Block Contract
 */
interface Block {
	/**
	 * Used for registering the Block. Treated as the constructor.
	 */
	public function register();

	/**
	 * Render method. Used for rendering the block output.
	 */
	public function render();
}
