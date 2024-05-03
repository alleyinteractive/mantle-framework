<?php
/**
 * Load_Configuration class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use InvalidArgumentException;
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
	 * Additional service configuration to register from the bootloader.
	 *
	 * @var array<string, array<mixed>>
	 */
	protected static $merge = [];

	/**
	 * Merge additional service providers to the list of providers.
	 *
	 * @param array<class-string<\Mantle\Support\Service_Provider>> $providers List of service providers.
	 */
	public static function merge( array $providers ): void {
		static::$merge = array_merge( static::$merge, $providers );
	}

	/**
	 * Load the configuration for the application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ): void {
		$config = $app->make( 'config' );

		// Load the configuration files if not loaded from cache.
		if ( ! $config->get( 'config.loaded_from_cache' ) ) {
			$this->load_configuration_files( $app, $config );
		}

		if ( ! empty( static::$merge ) ) {
			$this->load_merge_configuration( $config );
		}
	}

	/**
	 * Load specific configuration files to a repository.
	 *
	 * Will replace the configuration for each key as it processes the files and
	 * will merge configuration for those configuration keys that are mergeable.
	 *
	 * @param array<string, string[]> $files Files to load.
	 * @param Repository_Contract     $repository Repository to load to.
	 */
	protected function load_configuration_to_repository( array $files, Repository_Contract $repository ) {
		$filesystem = new Filesystem();

		foreach ( $files as $key => $config_files ) {
			foreach ( $config_files as $config_file ) {
				$config = $filesystem->get_require( $config_file );

				// Skip if the configuration is not an array.
				if ( ! is_array( $config ) ) {
					continue;
				}

				// If the configuration key does not exist, set it and continue early.
				if ( ! $repository->has( $key ) ) {
					$repository->set( $key, $config );

					continue;
				}

				$existing_config = $repository->get( $key );

				foreach ( $this->get_mergeable_options( $key ) as $option ) {
					$config[ $option ] = array_merge( $existing_config[ $option ] ?? [], $config[ $option ] ?? [] );
				}

				$repository->set( $key, $config );
			}
		}
	}

	/**
	 * Load the configuration from the files.
	 *
	 * @param Application         $app Application instance.
	 * @param Repository_Contract $repository Configuration Repository.
	 */
	protected function load_configuration_files( Application $app, Repository_Contract $repository ): void {
		$files = $this->get_configuration_files( $app );

		// Bail early if 'global.app' is not found.
		if ( empty( $files['global']['app'] ) ) {
			return;
		}

		// Load the root-level config.
		$this->load_configuration_to_repository( $files['global'], $repository );

		$env = $app->environment();

		// Load the environment-specific configurations if one exists.
		if ( ! empty( $files['env'][ $env ] ) ) {
			$this->load_configuration_to_repository( $files['env'][ $env ], $repository );
		}
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
	 * Find the configuration files to load.
	 *
	 * @param Application $app Application instance.
	 * @return array{global: array<string, string[]>, env: array<string, array<string, string[]>>}
	 */
	protected function get_configuration_files( Application $app ): array {
		$files = [
			'global' => [],
			'env'    => [],
		];

		$finder = Finder::create()
			->files()
			->name( '*.php' )
			->depth( '< 2' ) // Only descend two levels.
			->in( $this->get_configuration_directories( $app ) );

		foreach ( $finder as $file ) {
			$name = basename( $file->getRealPath(), '.php' );

			$dirname     = basename( dirname( $file->getRealPath() ) );
			$environment = match ( $dirname ) {
				// Ignore the 'config' directory as an environment.
				'config', 'configuration' => null,
				default => $dirname,
			};

			if ( $environment ) {
				$files['env'][ $environment ][ $name ][] = $file->getRealPath();
			} else {
				$files['global'][ $name ][] = $file->getRealPath();
			}
		}

		// Sort them to ensure a similar experience across all hosting environments.
		foreach ( $files as $type => $config_files ) {
			ksort( $files[ $type ], SORT_NATURAL );
		}

		return $files;
	}

	/**
	 * Load the additional configuration from the bootloader to the repository.
	 *
	 * @throws InvalidArgumentException If the configuration value is not an array.
	 * @throws InvalidArgumentException If the mergeable option is not an array.
	 *
	 * @param Repository_Contract $repository Configuration Repository.
	 */
	protected function load_merge_configuration( Repository_Contract $repository ): void {
		foreach ( static::$merge as $config_key => $values ) {
			if ( ! is_array( $values ) ) {
				throw new InvalidArgumentException( "The bootstrap-loaded configuration value for key '{$config_key}' must be an array." );
			}

			$config            = $repository->get( $config_key, [] );
			$mergeable_options = $this->get_mergeable_options( $config_key );

			foreach ( $values as $key => $value ) {
				if ( in_array( $key, $mergeable_options, true ) ) {
					if ( ! is_array( $value ) ) {
						throw new InvalidArgumentException( "The mergeable option '{$key}' for key '{$config_key}' must be an array." );
					}

					$config[ $key ] = array_merge( $config[ $key ] ?? [], $value );
				} else {
					$config[ $key ] = $value;
				}
			}

			$repository->set( $config_key, $config );

			static::$merge = [];
		}
	}

	/**
	 * Retrieve the mergeable options for the configuration.
	 *
	 * These configuration keys can be merged from across all configuration files.
	 *
	 * @param string $name Configuration name.
	 * @return array<string>
	 */
	protected function get_mergeable_options( string $name ): array {
		return [
			'app' => [ 'providers' ],
		][ $name ] ?? [];
	}
}
