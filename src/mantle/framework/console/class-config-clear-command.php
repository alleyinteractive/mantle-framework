<?php
/**
 * Config_Clear_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Contracts\Application;
use Mantle\Filesystem\Filesystem;

/**
 * Clear Config Cache Command
 */
class Config_Clear_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'config:clear';

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Delete the local Mantle cache for the configuration.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Delete the local Mantle cache for the configuration.';

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Command synopsis.
	 *
	 * @var array
	 */
	protected $synopsis = '';

	/**
	 * Flush Mantle's configuration cache.
	 *
	 * @param Filesystem $filesystem Filesystem instance.
	 */
	public function handle( Filesystem $filesystem ) {
		$this->files = $filesystem;

		$this->container['events']->dispatch( 'config-cache:clearing' );

		$this->delete_cached_files();

		$this->container['events']->dispatch( 'config-cache:cleared' );
	}

	/**
	 * Delete the cached files.
	 */
	protected function delete_cached_files() {
		$path = $this->container->get_cached_config_path();
		$this->log( 'Deleting: ' . $this->colorize( $path, 'yellow' ) );

		if ( $this->files->delete( $path ) ) {
			$this->success( 'Config cache file deleted.' );
		} else {
			$this->error( 'File not deleted.' );
		}
	}
}
