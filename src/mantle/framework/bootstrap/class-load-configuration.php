<?php
/**
 * Load_Configuration class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Exception;
use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Contracts\Config\Repository as Repository_Contract;
use Mantle\Support\Arr;
use Symfony\Component\Finder\Finder;
use Mantle\Support\Helpers;
use Mantle\Support\Str;

/**
 * Load the Application's Configuration
 */
class Load_Configuration {
	/**
	 * Load the configuration for the application.
	 *
	 * @todo Add cached config usage.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		$cached = $app->get_cached_config_path();

		$items = [];

		// Check if a cached configuration file exists.
		if ( is_file( $cached ) ) {
			$items = require $cached;

			$loaded_from_cache = true;
		}

		$config = new Repository( $items );

		// Set the global config alias.
		$app->instance( 'config', $config );

		// Load configuration files if the config hasn't been loaded from cache.
		if ( ! isset( $loaded_from_cache ) ) {
			$this->load_configuration_files( $app, $config );
		}
	}

	/**
	 * Load the configuration from the files.
	 *
	 * @param Application         $app Application instance.
	 * @param Repository_Contract $repository Configuration Repository.
	 *
	 * @throws Exception Thrown on missing 'app' config file.
	 */
	protected function load_configuration_files( Application $app, Repository_Contract $repository ) {
		$files = $this->get_configuration_files( $app );

		// Bail early if 'app' is not found.
		if ( empty( $files['app'] ) ) {
			throw new Exception( 'Unable to find the "app" configuration file.' );
		}

		// Filter the environment config files from the root-level ones.
		$environment_config = array_filter( $files, 'is_array' );
		$root_config_files  = array_filter( $files, 'is_string' );

		// Sort them to ensure a similar experience across all hosting environments.
		ksort( $environment_config, SORT_NATURAL );
		ksort( $root_config_files, SORT_NATURAL );

		// Load the root-level config.
		$this->load_configuration_to_repository( $root_config_files, $repository );

		// Load the environment-specific configurations if one exists.
		$env = $app->environment();
		if ( ! empty( $environment_config[ $env ] ) ) {
			$this->load_configuration_to_repository( $environment_config[ $env ], $repository, true );
		}
	}

	/**
	 * Find the configuration files to load.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function get_configuration_files( Application $app ): array {
		$path  = $app->get_config_path();
		$files = [];

		$finder = Finder::create()
			->files()
			->name( '*.php' )
			->depth( '< 2' ) // Only descend two levels.
			->in( $path );

		foreach ( $finder as $file ) {
			$name = basename( $file->getRealPath(), '.php' );

			// Get the environment the configuration file is from.
			$environment = str_replace( Str::trailing_slash( $path ), '', Str::trailing_slash( $file->getPath() ) );
			if ( empty( $environment ) ) {
				$files[ $name ] = $file->getRealPath();
			} else {
				$files[ Str::untrailing_slash( $environment ) ][ $name ] = $file->getRealPath();
			}
		}

		return $files;
	}

	/**
	 * Load specific configuration files to a repository
	 *
	 * @param string[]            $files Files to load.
	 * @param Repository_Contract $repository Repository to load to.
	 * @param bool                $merge Flag to merge the configuration instead of overwriting.
	 */
	protected function load_configuration_to_repository( array $files, Repository_Contract $repository, bool $merge = false ) {
		foreach ( $files as $key => $root_config_file ) {
			$config = require $root_config_file;

			if ( ! $merge || ! $repository->has( $key ) ) {
				$repository->set( $key, $config );
				continue;
			}

			$existing_config = $repository->get( $key );

			// Merge the configuration recursively.
			$dot_config = Arr::dot( $config );

			foreach ( $dot_config as $config_key => $config_value ) {
				Helpers\data_set( $existing_config, $config_key, $config_value, true );
			}

			$repository->set( $key, $existing_config );
		}
	}
}
