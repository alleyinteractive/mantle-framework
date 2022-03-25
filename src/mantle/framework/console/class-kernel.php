<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Contracts\Application;
use Mantle\Contracts\Console\Kernel as Kernel_Contract;
use Mantle\Contracts\Kernel as Core_Kernel_Contract;
use Mantle\Support\Traits\Loads_Classes;
use ReflectionClass;
use Throwable;

/**
 * Console Kernel
 */
class Kernel implements Kernel_Contract, Core_Kernel_Contract {
	use Loads_Classes;

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
		\Mantle\Framework\Bootstrap\Load_Environment_Variables::class,
		\Mantle\Framework\Bootstrap\Load_Configuration::class,
		\Mantle\Framework\Bootstrap\Register_Facades::class,
		\Mantle\Framework\Bootstrap\Register_Providers::class,
		\Mantle\Framework\Bootstrap\Boot_Providers::class,
		\Mantle\Framework\Bootstrap\Register_Cli_Commands::class,
	];

	/**
	 * The commands provided by the application.
	 *
	 * @var array
	 */
	protected $commands = [];

	/**
	 * Indicates if the Closure commands have been loaded.
	 *
	 * @var bool
	 */
	protected $commands_loaded = false;

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
		} catch ( Throwable $e ) {
			\WP_CLI::error( 'Error booting Console Kernel: ' . $e->getMessage() );
		}
	}

	/**
	 * Bootstrap the console.
	 */
	public function bootstrap() {
		$this->app->bootstrap_with( $this->bootstrappers(), $this );
	}

	/**
	 * Register the application's commands.
	 *
	 * @return void
	 */
	public function commands(): void {}

	/**
	 * Register all the commands in a set of directories.
	 *
	 * @param string ...$paths Paths to register.
	 * @return void
	 */
	protected function load( ...$paths ) {
		$namespace = $this->app->get_namespace();

		$this->commands = collect( $paths )
			->unique()
			->filter( fn ( string $path ) => is_dir( $path ) )
			->map( fn ( string $path ) => $this->classes_from_path( $path, $namespace . '\Console' ) )
			->flatten()
			->filter(
				fn ( string $class ) => class_exists( $class )
				&& is_subclass_of( $class, Command::class )
				&& ( new ReflectionClass( $class ) )->isInstantiable()
			)
			->filter()
			->merge( $this->commands )
			->unique()
			->all();
	}

	/**
	 * Register CLI Commands from the Application Kernel
	 */
	public function register_commands() {
		if ( ! $this->commands_loaded ) {
			$this->commands();

			foreach ( $this->commands as $command ) {
				$command = $this->app->make( $command );
				$command->register();
			}

			$this->commands_loaded = true;
		}
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers(): array {
		return $this->bootstrappers;
	}

	/**
	 * Log to the console.
	 *
	 * @param string $message Message to log.
	 */
	public function log( string $message ) {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::log( $message );
		}
	}
}
