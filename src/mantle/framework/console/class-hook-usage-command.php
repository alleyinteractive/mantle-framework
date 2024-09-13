<?php
/**
 * Hook_Usage_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use InvalidArgumentException;
use Mantle\Console\Command;
use Mantle\Contracts\Application;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function Mantle\Support\Helpers\collect;

/**
 * Hook Usage Command Command
 *
 * Search across a set of files for a reference to a specific hook.
 *
 * @todo Reflect on the current implementation and see if it can be improved.
 */
class Hook_Usage_Command extends Command {
	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Tabulate all the usage of a hook in the code base.';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = 'hook-usage {hook} {--search-path=} {--format=}';

	/**
	 * Paths to search.
	 *
	 * @var Collection
	 */
	protected $paths;

	/**
	 * Core hook methods to search for.
	 *
	 * @var string[]
	 */
	public const HOOK_METHODS = [
		'add_action',
		'add_action_side_effect',
		'add_filter',
		'add_filter_side_effect',
	];

	/**
	 * Callback for the command.
	 */
	public function handle(): int {
		$usage = $this->get_usage();

		if ( $usage->is_empty() ) {
			$this->error( 'No usage found.' );
			return Command::FAILURE;
		}

		$this->format_data(
			$this->option( 'format', 'table' ),
			[
				'file',
				'line',
				'method',
			],
			$usage->all(),
		);

		return Command::SUCCESS;
	}

	/**
	 * Retrieve the usage of a hook.
	 *
	 * @todo Account for service providers!
	 *
	 * @throws InvalidArgumentException Thrown on invalid search path.
	 */
	public function get_usage(): Collection {
		$this->set_paths();

		if ( $this->paths->is_empty() ) {
			throw new InvalidArgumentException( 'No paths specified.' );
		}

		// Collect all the files.
		$usage = collect();
		foreach ( $this->paths as $path ) {
			$usage = $usage->merge( $this->read_path( $path ) );
		}

		return $usage
			->map( fn ( $file ) => $this->read_file( $file ) )
			->flatten( 1 );
	}

	/**
	 * Read a specific path for files.
	 *
	 * @param string $path
	 */
	protected function read_path( string $path ): Collection {
		if ( is_file( $path ) ) {
			// Only permit PHP files through.
			if ( 'php' !== pathinfo( $path, PATHINFO_EXTENSION ) ) {
				return collect();
			}

			return collect( [ $path ] );
		}

		$cache = $this->get_cache_for_path( $path );
		if ( $cache ) {
			return $cache;
		}

		$paths_to_ignore = [
			'*/tests',
			'*/tests/*',
			'*/vendor',
			'*/vendor/*',
		];

		// Disable ignoring paths for unit testing.
		if ( defined( 'MANTLE_PHPUNIT_INCLUDES_PATH' ) ) {
			$paths_to_ignore = [];
		}

		$dir   = new RecursiveDirectoryIterator( $path );
		$files = new RecursiveCallbackFilterIterator(
			$dir,
			function ( \SplFileInfo $current, $key, RecursiveDirectoryIterator $iterator ) use ( $paths_to_ignore ): bool {
				if ( Str::is( $paths_to_ignore, $current->getRealPath() ) ) {
					return false;
				}

				if ( $iterator->hasChildren() ) {
					return true;
				}

				if ( ! $current->isFile() || 'php' !== $current->getExtension() ) {
					return false;
				}

				return true;
			}
		);

		$list     = collect();
		$iterator = new RecursiveIteratorIterator( $files );

		foreach ( $iterator as $file ) {
			$list->add( $file->getRealPath() );
		}

		$this->set_cache_for_path( $path, $list );

		return $list;
	}

	/**
	 * Read a file and extract the references inside.
	 *
	 * @param string $file File to parse.
	 */
	protected function read_file( string $file ): Collection {
		$references = collect();
		$contents   = file_get_contents( $file ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		foreach ( static::HOOK_METHODS as $method ) {
			preg_match_all(
				'/[^A-Za-z_](' . preg_quote( $method, '#' ) . ')\(\s*?[\'"]' . preg_quote( $this->argument( 'hook' ), '#' ) . '[\'"]\s*?/m',
				$contents,
				$matches,
				PREG_OFFSET_CAPTURE
			);

			if ( empty( $matches[1] ) ) {
				continue;
			}

			foreach ( $matches[1] as $match ) {
				[ $method, $char_pos ] = $match;

				$line = Str::line_number( $contents, $char_pos );

				$references->add( compact( 'file', 'line', 'method' ) );
			}
		}

		unset( $contents );

		return $references;
	}

	/**
	 * Determine if the cache should be used.
	 */
	protected function should_use_cache(): bool {
		if ( ! app()->is_environment( 'local' ) ) {
			return false;
		}

		return ! defined( 'MANTLE_PHPUNIT_INCLUDES_PATH' );
	}

	/**
	 * Get the cached files for a path.
	 *
	 * @param string $path Path to retrieve the cache for.
	 */
	protected function get_cache_for_path( string $path ): ?Collection {
		if ( ! $this->should_use_cache() ) {
			return null;
		}

		$file = $this->get_cache_file_for_path( $path );
		if ( ! file_exists( $file ) ) {
			return null;
		}

		// Check if the file is stale (older than today).
		if ( filemtime( $file ) < ( time() - DAY_IN_SECONDS ) ) {
			// Delete the cached file if it is stale.
			@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
			return null;
		}

		$files = require_once $file;
		return collect( $files );
	}

	/**
	 * Set the cache for a specific path.
	 *
	 * @param string     $path Path to cache for.
	 * @param Collection $files Collection of files.
	 *
	 * @throws RuntimeException Thrown on error writing cache.
	 */
	protected function set_cache_for_path( string $path, Collection $files ) {
		if ( ! $this->should_use_cache() ) {
			return;
		}

		$file = $this->get_cache_file_for_path( $path );

		if ( ! file_put_contents( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			$file,
			'<?php return ' . var_export( $files->all(), true ) . ';' // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		) ) {
			throw new RuntimeException( 'Error writing cache file: ' . $file );
		}
	}

	/**
	 * Get the file path for a cached file for a specific path.
	 *
	 * @todo Move to uploads folder for non-writeable environments.
	 *
	 * @param string $path Path to cache against.
	 */
	protected function get_cache_file_for_path( string $path ): string {
		$path = md5( $path );
		return app()->get_cache_path() . "/hook-usage-{$path}.php";
	}

	/**
	 * Get the paths for the hook search.
	 *
	 * @todo Filter out inactive plugins from the path list.
	 */
	protected function set_paths() {
		if ( ! $this->option( 'search-path' ) ) {
			$paths = collect( [ defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : getcwd() ] );
		} else {
			$paths = collect( explode( ',', $this->option( 'search-path' ) ) );
		}

		$this->paths = $paths
			->trim()
			->unique()
			->filter(
				function ( $path ): bool {
					if ( ! is_file( $path ) && ! is_dir( $path ) ) {
						return false;
					}

					return true;
				}
			)
			->values();
	}
}
