<?php
/**
 * Clear_Cache_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Contracts\Application;

/**
 * Clear Cache Command
 */
class Clear_Cache_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cache:clear';

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Delete the local Mantle cache (not the WordPress object cache).';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Delete the local Mantle cache (not the WordPress object cache).';

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Command synopsis.
	 *
	 * @var array
	 */
	protected $synopsis = '';

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Flush Mantle's local cache.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$this->app['events']->dispatch( 'cache:clearing' );

		$this->delete_cached_files();

		$this->app['events']->dispatch( 'cache:cleared' );
	}

	/**
	 * Delete the cached files.
	 */
	protected function delete_cached_files() {
		$files = glob( $this->app->get_cache_path() . '/*.php' );

		foreach ( $files as $file ) {
			$this->log( "Deleting: [$file]" );

			try {
				unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
			} catch ( \Throwable $e ) {
				$this->log( 'Error deleting: ' . $e->getMessage() );
			}
		}

		$this->log( 'All files deleted.' );
	}
}
