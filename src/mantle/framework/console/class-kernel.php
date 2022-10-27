<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Contracts\Application;
use Mantle\Contracts\Console\Kernel as Kernel_Contract;
use Mantle\Console\Application as Console_Application;
use Mantle\Contracts\Console\Application as Console_Application_Contract;
use Mantle\Contracts\Kernel as Core_Kernel_Contract;
use Mantle\Support\Traits\Loads_Classes;
use Mantle\Testkit\Exception_Handler;
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
		// todo: reenable when ready.
		// \Mantle\Framework\Bootstrap\Register_Providers::class,
		// \Mantle\Framework\Bootstrap\Boot_Providers::class,
		\Mantle\Framework\Bootstrap\Register_Cli_Commands::class,
	];

	/**
	 * The commands provided by the application.
	 *
	 * @var array
	 */
	protected $commands = [];

	/**
	 * Console application.
	 *
	 * @var Console_Application_Contract
	 */
	protected Console_Application_Contract $console_application;

	/**
	 * Indicates if the Closure commands have been loaded.
	 *
	 * @var bool
	 */
	protected bool $commands_loaded = false;

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
	 * @return int
	 */
	public function handle( $input, $output = null ) {
		try {
			$this->bootstrap();

			// temporary.
			$this->app->singleton( 'files', fn () => new \Mantle\Filesystem\Filesystem() );

			Console_Application::starting( fn ( $app ) => $app->resolve( Config_Cache_Command::class ) );

			return $this->get_console_application()->run( $input, $output );
		} catch ( Throwable $e ) {
			$this->report_exception( $e );
			$this->render_exception( $output, $e );

			return 1;
		}
	}

	/**
	 * Bootstrap the console.
	 */
	public function bootstrap() {
		$this->app->bootstrap_with( $this->bootstrappers(), $this );
	}

	/**
	 * Resolve the instance of the console application.
	 *
	 * @return Console_Application_Contract
	 */
	public function get_console_application(): Console_Application_Contract {
		if ( ! isset( $this->console_application ) ) {
			$this->console_application = new Console_Application( $this->app );
		}

		return $this->console_application;
	}

	/**
	 * Set the console application instance.
	 */
	public function set_console_application( Console_Application_Contract $app ): void {
		$this->console_application = $app;
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

			Console_Application::starting(
				fn ( Console_Application $app ) => $app->resolve_commands( $this->commands )
			);

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
		dd( 'to be replaced', $message );
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::log( $message );
		}
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return void
	 */
	protected function report_exception( Throwable $e ) {
		$this->app[ Exception_Handler::class ]->report( $e );
	}

	/**
	 * Render the exception to a response.
	 *
	 * @param Request   $request Request instance.
	 * @param Throwable $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function render_exception( $request, Throwable $e ) {
		return $this->app[ Exception_Handler::class ]->render( $request, $e );
	}
}
