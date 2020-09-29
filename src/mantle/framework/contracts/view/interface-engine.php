<?php
/**
 * Engine interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\View;

/**
 * Engine Interface
 */
interface Engine {
	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  string $path View path.
	 * @param  array  $data View data.
	 * @return string
	 */
	public function get( string $path, array $data = [] ): string;
}
