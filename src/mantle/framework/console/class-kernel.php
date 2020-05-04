<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Framework\Application;
use Mantle\Framework\Contracts\Console\Kernel as Kernel_Contract;
use Exception;

/**
 * Console Kernel
 */
class Kernel implements Kernel_Contract {
	/**
	 * The application implementation.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array
	 */
	protected $bootstrappers = [
		\Mantle\Framework\Bootstrap\Register_Providers::class,
		\Mantle\Framework\Bootstrap\Boot_Providers::class,
		\Mantle\Framework\Bootstrap\Register_Cli_Commands::class,
	];

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Bootstrap the console.
	 *
	 * @todo Add better error handling.
	 */
	public function handle() {
		try {
			$this->bootstrap();
		} catch ( Exception $e ) {
			\WP_CLI::error( 'Error booting console: ' . $e->getMessage() );
		}
	}

	/**
	 * Bootstrap the console.
	 */
	public function bootstrap() {
		$this->app->bootstrap_with( $this->bootstrappers() );
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers(): array {
		return $this->bootstrappers;
	}
}
