<?php
/**
 * Engine class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\View\Engines;

/**
 * View Engine
 */
abstract class Engine {
	/**
	 * The view that was last to be rendered.
	 *
	 * @var string
	 */
	protected $last_rendered;

	/**
	 * Get the last view that was rendered.
	 *
	 * @return string
	 */
	public function get_last_rendered(): string {
		return $this->last_rendered;
	}
}
