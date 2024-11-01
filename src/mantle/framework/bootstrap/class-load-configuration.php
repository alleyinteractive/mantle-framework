<?php
/**
 * Load_Configuration class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use InvalidArgumentException;
use Mantle\Contracts\Application;
use Mantle\Contracts\Config\Repository as Repository_Contract;
use Mantle\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\collect;

/**
 * Load the Application's Configuration from the filesystem.
 */
class Load_Configuration {
	/**
	 * Additional configuration to register from the bootloader.
	 *
	 * @var array<string, array<mixed>>
	 */
	protected static $merge = [];

	/**
	 * Merge additional configuration.
	 *
	 * @param array<string, array<mixed>> $config Configuration to merge.
	 */
	public static function merge( array $config ): void {
		static::$merge = array_merge( static::$merge, $config );
	}

	/**
	 * Load the configuration for the application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ): void {
		$config = $app->make( 'config' );

		// Load the configuration files if not already loaded from cache.
		if ( ! $config->get( 'config.loaded_from_cache' ) ) {
			$this->load_configuration_files( $app, $config );
		}

		if ( ! empty( static::$merge ) ) {
			$this->load_late_configuration( $config );
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

				$repository->set( $key, $this->merge_configuration( $key, $repository->get( $key ), $config ) );
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

		$this->load_configuration_to_repository( $files['global'], $repository );

		$env = $app->environment();

		// Load the environment-specific configurations if one exists.
		if ( ! empty( $files['environment'][ $env ] ) ) {
			$this->load_configuration_to_repository( $files['environment'][ $env ], $repository );
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
	 * @return array{global: array<string, string[]>, environment: array<string, array<string, string[]>>}
	 */
	protected function get_configuration_files( Application $app ): array {
		$files = [
			'global'      => [],
			'environment' => [],
		];

		$finder = Finder::create()
			->files()
			->name( '*.php' )
			->depth( '< 2' )
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
				$files['environment'][ $environment ][ $name ][] = $file->getRealPath();
			} else {
				$files['global'][ $name ][] = $file->getRealPath();
			}
		}

		return $files;
	}

	/**
	 * Retrieve the base configuration.
	 *
	 * @return array<string, array<mixed>>
	 */
	protected function get_base_configuration(): array {
		$config = [];

		foreach ( Finder::create()->files()->name( '*.php' )->depth( '< 2' )->in( dirname( __DIR__, 4 ) . '/config' ) as $file ) {
			$name = basename( $file->getRealPath(), '.php' );

			$config[ $name ] = $this->merge_configuration(
				$name, $config[ $name ] ?? [],
				require $file->getRealPath() // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			);
		}

		return $config;
	}

	/**
	 * Load the additional configuration from the bootloader to the repository.
	 *
	 * @throws InvalidArgumentException If the configuration value is not an array.
	 *
	 * @param Repository_Contract $repository Configuration Repository.
	 */
	protected function load_late_configuration( Repository_Contract $repository ): void {
		foreach ( static::$merge as $config_key => $values ) {
			if ( ! is_array( $values ) ) {
				throw new InvalidArgumentException( "The bootloader configuration value for key '{$config_key}' must be an array." );
			}

			$repository->set(
				$config_key,
				$this->merge_configuration( $config_key, $repository->get( $config_key, [] ), $values ),
			);

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

	/**
	 * Merge two configurations together.
	 *
	 * Will merge the two configurations together and merge any mergeable options.
	 *
	 * @throws InvalidArgumentException If the mergeable option is not an array.
	 * @see Load_Configuration::get_mergeable_options()
	 *
	 * @param string $config_name Configuration name.
	 * @param array  $config_a    First configuration.
	 * @param array  $config_b    Second configuration.
	 */
	protected function merge_configuration( string $config_name, array $config_a, array $config_b ): array {
		$new_config = array_merge( $config_a, $config_b );

		foreach ( $this->get_mergeable_options( $config_name ) as $option ) {
			if ( ! isset( $config_a[ $option ] ) || ! isset( $config_b[ $option ] ) ) {
				continue;
			}

			if ( ! is_array( $config_a[ $option ] ) || ! is_array( $config_b[ $option ] ) ) {
				throw new InvalidArgumentException( "The mergeable option '{$option}' for key '{$config_name}' must be an array." );
			}

			$new_config[ $option ] = array_merge( $config_a[ $option ] ?? [], $config_b[ $option ] ?? [] );
		}

		return $new_config;
	}
}
