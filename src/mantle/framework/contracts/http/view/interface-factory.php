<?php
/**
 * Factory interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Http\View;

/**
 * View Factory Contract
 */
interface Factory {
	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param array|string $key Key to share.
	 * @param mixed|null   $value Value to share.
	 * @return mixed
	 */
	public function share( $key, $value = null );

	/**
	 * Get an item from the shared data.
	 *
	 * @param string $key Key to get item by.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function shared( $key, $default = null );

	/**
	 * Get all of the shared data for the environment.
	 *
	 * @return array
	 */
	public function get_shared(): array;

	/**
	 * Get the rendered contents of a view.
	 *
	 * @param string $view View name.
	 * @param array  $data Data to pass to the view.
	 * @return string
	 */
	public function make( string $view, array $data = [] ): string;
}
