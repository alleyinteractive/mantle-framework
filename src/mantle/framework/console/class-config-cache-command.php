<?php
/**
 * Config_Clear_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use LogicException;
use Mantle\Contracts\Application;
use Mantle\Contracts\Console\Kernel;
use Mantle\Console\Command;
use Mantle\Filesystem\Filesystem;
use Throwable;

/**
 * Clear Config Cache Command
 */
class Config_Cache_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'config:cache';

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
	 * Flush Mantle's local cache.
	 *
	 * @param Filesystem $filesystem Filesystem instance.
	 *
	 * @throws LogicException Thrown on error writing config file.
	 */
	public function handle( Filesystem $filesystem ): void {
		$this->files = $filesystem;

		$this->call( 'mantle config:clear' );

		$path   = $this->container->get_cached_config_path();
		$config = $this->get_fresh_configuration();

		$this->files->put(
			$path,
			'<?php return ' . var_export( $config, true ) . ';' . PHP_EOL // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		);

		try {
			require $path;
		} catch ( Throwable $e ) {
			$this->files->delete( $path );

			throw new LogicException( 'Your configuration files are not serializable.', 0, $e );
		}

		$this->container['events']->dispatch( 'config-cache:cached' );

		$this->success( 'Configuration cached successfully.' );
	}

	/**
	 * Boot a fresh copy of the application configuration.
	 */
	protected function get_fresh_configuration() : array {
		$app = require $this->container->get_bootstrap_path( '/app.php' );
		$app->set_base_path( $this->container->get_base_path() );
		$app->make( Kernel::class )->bootstrap();

		return $app['config']->all();
	}
}
