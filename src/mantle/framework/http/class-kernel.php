<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http;

use Mantle\Framework\Application;
use Mantle\Framework\Contracts\Http\Kernel as Kernel_Contract;
use Exception;

/**
 * HTTP Kernel
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
			\wp_die( 'Error booting console: ' . $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
