<?php
/**
 * Clear_Cache_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Contracts\Application;

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
	 * Command synopsis.
	 *
	 * @var array
	 */
	protected $synopsis = '';

	/**
	 * Flush Mantle's local cache.
	 */
	public function handle() {
		$this->container['events']->dispatch( 'cache:clearing' );

		$this->delete_cached_files();

		$this->container['events']->dispatch( 'cache:cleared' );
	}

	/**
	 * Delete the cached files.
	 */
	protected function delete_cached_files() {
		$files = glob( $this->container->get_cache_path() . '/*.php' );

		foreach ( $files as $file ) {
			$this->log( 'Deleting: ' . $this->colorize( $file, 'yellow' ) );

			try {
				unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
			} catch ( \Throwable $e ) {
				$this->log( 'Error deleting: ' . $e->getMessage() );
			}
		}

		$this->success( 'All files deleted.' );
	}
}
