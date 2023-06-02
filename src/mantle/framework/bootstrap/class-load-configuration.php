<?php
/**
 * Load_Configuration class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Application\Application;
use Mantle\Contracts\Config\Repository as Repository_Contract;
use Mantle\Filesystem\Filesystem;
use Mantle\Support\Arr;
use Symfony\Component\Finder\Finder;
use Mantle\Support\Helpers;
use Mantle\Support\Str;

use function Mantle\Support\Helpers\collect;

/**
 * Load the Application's Configuration from the filesystem.
 */
class Load_Configuration {
	/**
	 * Load the configuration for the application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		$config = $app->make( 'config' );

		// Load the configuration files if not loaded from cache.
		if ( ! $config->get( 'config.loaded_from_cache' ) ) {
			$this->load_configuration_files( $app, $config );
		}
	}

	/**
	 * Load the configuration from the files.
	 *
	 * @param Application         $app Application instance.
	 * @param Repository_Contract $repository Configuration Repository.
	 * @return void
	 */
	protected function load_configuration_files( Application $app, Repository_Contract $repository ): void {
		$files = $this->get_configuration_files( $app );

		// Bail early if 'app' is not found.
		if ( empty( $files['app'] ) ) {
			return;
		}

		// Filter the environment config files from the root-level ones.
		$environment_config = array_filter( $files, 'is_array' );
		$root_config_files  = array_filter( $files, 'is_string' );

		// Sort them to ensure a similar experience across all hosting environments.
		ksort( $environment_config, SORT_NATURAL );
		ksort( $root_config_files, SORT_NATURAL );

		// Load the root-level config.
		$this->load_configuration_to_repository( $root_config_files, $repository );

		$env = $app->environment();

		// Load the environment-specific configurations if one exists.
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
		$files = [];

		$finder = Finder::create()
			->files()
			->name( '*.php' )
			->depth( '< 2' ) // Only descend two levels.
			->in( $this->get_configuration_directories( $app ) );

		foreach ( $finder as $file ) {
			$name = basename( $file->getRealPath(), '.php' );

			// Get the environment the configuration file is from.
			$environment = basename( dirname( $file->getRealPath() ) );

			// Ignore the 'config' directory as an environment.
			if ( in_array( $environment, [ 'config', 'configuration' ], true ) ) {
				$environment = null;
			}

			if ( empty( $environment ) ) {
				$files[ $name ] = $file->getRealPath();
			} else {
				$files[ Str::untrailing_slash( $environment ) ][ $name ] = $file->getRealPath();
			}
		}

		return $files;
	}

	/**
	 * Retrieve the configuration directories to load from.
	 *
	 * @param Application $app Application instance.
	 * @return array<int, string>
	 */
	protected function get_configuration_directories( Application $app ): array {
		/**
		 * Filter the configuration directories to load from.
		 *
		 * @param array<int, string> $paths Configuration directories.
		 * @param Application        $app Application instance.
		 */
		$paths = $app['events']->dispatch(
			'mantle_config_paths',
			[
				[
					dirname( __DIR__, 4 ) . '/config',
					$app->get_config_path(),
				],
				$app,
			],
		);

		return collect( $paths )
			->unique()
			->filter(
				fn ( $dir ) => is_string( $dir ) && is_dir( $dir ),
			)
			->values()
			->all();
	}

	/**
	 * Load specific configuration files to a repository
	 *
	 * @param string[]            $files Files to load.
	 * @param Repository_Contract $repository Repository to load to.
	 * @param bool                $merge Flag to merge the configuration instead of overwriting.
	 */
	protected function load_configuration_to_repository( array $files, Repository_Contract $repository, bool $merge = false ) {
		$filesystem = new Filesystem();

		foreach ( $files as $key => $root_config_file ) {
			$config = $filesystem->get_require( $root_config_file );

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
