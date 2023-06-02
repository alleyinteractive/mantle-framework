<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Closure;
use Mantle\Console\Application as Console_Application;
use Mantle\Console\Closure_Command;
use Mantle\Console\Command;
use Mantle\Console\Events\Lightweight_Event_Dispatcher;
use Mantle\Contracts\Application;
use Mantle\Contracts\Console\Application as Console_Application_Contract;
use Mantle\Contracts\Console\Kernel as Kernel_Contract;
use Mantle\Contracts\Exceptions\Handler as Exception_Handler;
use Mantle\Support\Traits\Loads_Classes;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

use function Mantle\Support\Helpers\collect;

/**
 * Console Kernel
 */
class Kernel implements Kernel_Contract {
	use Loads_Classes;

	/**
	 * The application implementation.
	 *
	 * @var Application|null
	 */
	protected $app;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array
	 */
	protected $bootstrappers = [
		\Mantle\Framework\Bootstrap\Load_Configuration::class,
		\Mantle\Framework\Bootstrap\Register_Aliases::class,
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

			return Command::FAILURE;
		}
	}

	/**
	 * Run the console application by command name.
	 *
	 * @param string $command Command name.
	 * @param array  $parameters Command parameters.
	 * @param mixed  $output_buffer Output buffer.
	 * @return int
	 */
	public function call( string $command, array $parameters = [], $output_buffer = null ) {
		$this->bootstrap();

		return $this->get_console_application()->call( $command, $parameters, $output_buffer );
	}

	/**
	 * Test a console command by name.
	 *
	 * @param string $command Command name.
	 * @param array  $parameters Command parameters.
	 * @return CommandTester
	 */
	public function test( string $command, array $parameters = [] ): CommandTester {
		$this->bootstrap();

		return $this->get_console_application()->test( $command, $parameters );
	}

	/**
	 * Register a new Closure based command with a signature.
	 *
	 * @param string  $signature Command signature.
	 * @param Closure $callback Command callback.
	 * @return Closure_Command
	 */
	public function command( string $signature, Closure $callback ): Closure_Command {
		$command = new Closure_Command( $signature, $callback );

		Console_Application::starting(
			fn ( Console_Application $app ) => $app->resolve( $command )
		);

		return $command;
	}

	/**
	 * Bootstrap the console.
	 */
	public function bootstrap() {
		if ( ! $this->app->is_running_in_console() ) {
			return;
		}

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
	 * @param OutputInterface $output Output interface.
	 * @param Throwable       $e Exception thrown.
	 * @return void
	 */
	protected function render_exception( OutputInterface $output, Throwable $e ) {
		$this->app[ Exception_Handler::class ]->render_for_console( $output, $e );
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
