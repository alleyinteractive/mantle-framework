<?php
/**
 * Loads_Base_Configuration trait file
 *
 * @package Mantle
 */

namespace Mantle\Application\Concerns;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Framework\Console\Kernel;
use Mantle\Support\Environment;

/**
 * Load a base configuration for Mantle to operate.
 *
 * @mixin \Mantle\Application\Application
 */
trait Loads_Base_Configuration {
	/**
	 * Load the base configuration for the application.
	 */
	public function load_base_configuration() {
		$cached = $this->get_cached_config_path();

		// Check if a cached configuration file exists. If found, load it.
		if ( is_file( $cached ) ) {
			$items = require $cached;

			$loaded_from_cache = true;
		} else {
			$items = [];
		}

		$config = new Repository( (array) $items );

		// Set the global config alias.
		$this->instance( 'config', $config );

		// Load configuration files if the config hasn't been loaded from cache.
		if ( isset( $loaded_from_cache ) ) {
			$config->set( 'config.loaded_from_cache', true );
		}
	}
}
