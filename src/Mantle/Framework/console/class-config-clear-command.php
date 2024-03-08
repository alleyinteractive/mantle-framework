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
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Delete the local Mantle cache for the configuration.';

	/**
	 * Filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Flush Mantle's configuration cache.
	 *
	 * @param Filesystem $filesystem Filesystem instance.
	 */
	public function handle( Filesystem $filesystem ): int {
		$this->files = $filesystem;

		$this->container['events']->dispatch( 'config-cache:clearing' );

		$status = $this->delete_cached_files();

		$this->container['events']->dispatch( 'config-cache:cleared' );

		return $status;
	}

	/**
	 * Delete the cached files.
	 */
	protected function delete_cached_files(): int {
		$path = $this->container->get_cached_config_path();

		if ( ! $this->files->exists( $path ) ) {
			return Command::SUCCESS;
		}

		$this->line( 'Deleting: ' . $this->colorize( $path, 'yellow' ) );

		if ( $this->files->delete( $path ) ) {
			$this->success( 'Config cache file deleted.' );
			return Command::SUCCESS;
		} else {
			$this->error( 'File not deleted.' );
			return Command::FAILURE;
		}
	}
}
