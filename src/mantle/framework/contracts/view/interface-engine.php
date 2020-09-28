<?php

namespace Mantle\Framework\Contracts\View;

interface Engine {
	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  string  $path View path.
	 * @param  array  $data View data.
	 * @return string
	 */
	public function get( string $path, array $data = [] ): string;
}
