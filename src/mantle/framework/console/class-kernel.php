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
use Mantle\Console\Command;
use Mantle\Console\Events\Lightweight_Event_Dispatcher;
use Mantle\Contracts\Console\Application as Console_Application_Contract;
use Mantle\Contracts\Kernel as Core_Kernel_Contract;
use Mantle\Support\Traits\Loads_Classes;
use Mantle\Contracts\Exceptions\Handler as Exception_Handler;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;
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
	 * Output interface
	 *
	 * @var OutputInterface
	 */
	protected ?OutputInterface $output;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;

		$this->ensure_environment_is_set();
		$this->register_wpcli_command();
	}

	/**
	 * Run the console application
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 */
	public function handle( $input = null, $output = null ) {
		$this->output = $output;

		try {
			$this->bootstrap();

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
		// Replace the event dispatcher when running in isolation mode.
		if ( $this->app->is_running_in_console_isolation() ) {
			$this->app->singleton(
				'events',
				fn ( $app ) => new Lightweight_Event_Dispatcher( $app ),
			);
		}

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
	 *
	 * Set the instance of the Symfony console application.
	 *
	 * @param Console_Application_Contract $app Console application instance.
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
		if ( isset( $this->output ) ) {
			$this->output->writeln( $message );
		}
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @todo Improve exception handling in the console.
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
	 * @param Throwable $e Exception thrown.
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function render_exception( $request, Throwable $e ) {
		return $this->app[ Exception_Handler::class ]->render( $request, $e );
	}

	/**
	 * Ensure the WordPress environment is setup for isolation mode.
	 */
	protected function ensure_environment_is_set() {
		if ( ! $this->app->is_running_in_console_isolation() ) {
			return;
		}

		defined( 'ABSPATH' ) || define( 'ABSPATH', preg_replace( '#/wp-content/.*$#', '/', __DIR__ ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
	}

	/**
	 * Register a proxy WP-CLI command that will proxy back to the Symfony
	 * application.
	 */
	protected function register_wpcli_command() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		\WP_CLI::add_command(
			Command::PREFIX,
			function () {
				$status = $this->handle(
					new \Symfony\Component\Console\Input\ArgvInput( array_slice( $_SERVER['argv'] ?? [], 1 ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					new \Symfony\Component\Console\Output\ConsoleOutput(),
				);

				exit( (int) $status );
			},
			[
				'shortdesc' => __( 'Mantle Framework Command Line Interface', 'mantle' ),
			]
		);
	}

	/**
	 * Terminate the kernel.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface $input
	 * @param  int                                             $status
	 * @return void
	 */
	public function terminate( $input, $status ): void {
		if ( isset( $this->app ) ) {
			$this->app->terminate();
		}
	}
}
