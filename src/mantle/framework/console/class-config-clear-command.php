<?php
/**
 * Config_Clear_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Filesystem\Filesystem;

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
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 * @param Filesystem  $filesystem Filesystem instance.
	 */
	public function __construct( Application $app, Filesystem $filesystem ) {
		$this->app   = $app;
		$this->files = $filesystem;
	}

	/**
	 * Flush Mantle's configuration cache.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$this->app['events']->dispatch( 'config-cache:clearing' );

		$this->delete_cached_files();

		$this->app['events']->dispatch( 'config-cache:cleared' );
	}

	/**
	 * Delete the cached files.
	 */
	protected function delete_cached_files() {
		$path = $this->app->get_cached_config_path();
		$this->log( "Deleting: [{$path}]" );

		if ( $this->files->delete( $path ) ) {
			$this->log( 'All files deleted.' );
		} else {
			$this->log( 'File not deleted.' );
		}
	}
}
