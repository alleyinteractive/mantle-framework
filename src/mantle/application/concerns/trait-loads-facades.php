<?php
/**
 * Loads_Facades class file.
 *
 * @package Mantle
 */

namespace Mantle\Application\Concerns;

use Mantle\Framework\Alias_Loader;
use Mantle\Application\Application;
use Mantle\Facade\Facade;
use Mantle\Framework\Manifest\Package_Manifest;

/**
 * Register the Facades for the Application
 *
 * @mixin \Mantle\Application\Application
 */
trait Loads_Facades {
	/**
	 * Bootstrap the given application's Facades.
	 */
	public function load_facades() {
		Facade::clear_resolved_instances();
		Facade::set_facade_application( $this );
	}
}
