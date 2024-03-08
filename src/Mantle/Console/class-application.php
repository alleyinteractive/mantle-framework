<?php
/**
 * Application class file
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Application as Application_Contract;
use Mantle\Contracts\Console\Application as Console_Application_Contract;
use Mantle\Support\Arr;
use Symfony\Component\Console\Application as Console_Application;
use Symfony\Component\Console\Command\Command as Symfony_Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

/**
 * Console Application
 *
 * Not to be confused with the Mantle Application/Container, this is an instance
 * of the Symfony Console Application.
 */
class Application extends Console_Application implements Console_Application_Contract {
	/**
	 * Array of closures to fire when the application is booted.
	 *
	 * @var Closure[]
	 */
	protected static array $bootstrappers = [];

	/**
	 * Register a console "starting" bootstrapper.
	 *
	 * @param Closure $callback Callback to run.
	 */
	public static function starting( Closure $callback ): void {
		static::$bootstrappers[] = $callback;
	}

	/**
	 * Forget all of the bootstrappers for the application.
	 */
	public static function forget_bootstrappers(): void {
		static::$bootstrappers = [];
	}

	/**
	 * Constructor.
	 *
	 * @param Application_Contract $container
	 */
	public function __construct( protected Application_Contract $container ) {
		parent::__construct(
			$this->container['config']->get( 'app.name', 'Mantle' ),
			$this->container['config']->get( 'app.version', '1.0.0' ),
		);

		$this->setAutoExit( false );
		$this->setCatchExceptions( false );

		$this->bootstrap();
	}

	/**
	 * Bootstrap the console application.
	 */
	protected function bootstrap() {
		// Fire off a starting event for the application to listen to.
		$this->container['events']->dispatch( 'console.starting', $this );

		foreach ( static::$bootstrappers as $bootstrapper ) {
			$bootstrapper( $this );
		}
	}

	/**
	 * Run the command through the console application.
	 *
	 * @todo Add event dispatching for before and after firing the command.
	 * @param InputInterface|null  $input Input interface.
	 * @param OutputInterface|null $output Output interface.
	 */
	public function run( InputInterface $input = null, OutputInterface $output = null ): int { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::run( $input, $output );
	}

	/**
	 * Run a console command by name.
	 *
	 * @todo Add support for non-Mantle commands (e.g. WP-CLI).
	 *
	 * @param string               $command Command name.
	 * @param array                $parameters Command parameters.
	 * @param OutputInterface|null $output_buffer Output buffer.
	 *
	 * @throws InvalidArgumentException Thrown if the command is not a Mantle command.
	 */
	public function call( string $command, array $parameters = [], $output_buffer = null ): int {
		if ( ! $this->has( $command ) ) {
			throw new InvalidArgumentException( "Command [{$command}] does not exist." );
		}

		return $this->run(
			new ArrayInput( array_merge( [ 'command' => $command ], $parameters ) ),
			$output_buffer ?: new BufferedOutput(),
		);
	}

	/**
	 * Test a console command by name.
	 *
	 * @param string $command Command name.
	 * @param array  $parameters Command parameters.
	 */
	public function test( string $command, array $parameters = [] ): CommandTester {
		$command = $this->find( $command );

		$tester = new CommandTester( $command );

		$tester->execute( array_merge( [ 'command' => $command ], $parameters ) );

		return $tester;
	}

	/**
	 * Resolve a command through the console application.
	 *
	 * @param Symfony_Command|Command|string $command
	 */
	public function resolve( Symfony_Command|Command|string $command ): static {
		if ( is_string( $command ) ) {
			return $this->resolve( $this->container->make( $command ) );
		}

		if ( $command instanceof Command ) {
			$command->set_container( $this->container );
		}

		parent::add( $command );

		return $this;
	}

	/**
	 * Resolve an array of commands through the console application.
	 *
	 * @param array|string|Symfony_Command|Command $commands
	 */
	public function resolve_commands( $commands ): static {
		$commands = Arr::wrap( $commands );

		foreach ( $commands as $command ) {
			$this->resolve( $command );
		}

		return $this;
	}

	/**
	 * Render a throwable for the console.
	 *
	 * @param Throwable       $e
	 * @param OutputInterface $output
	 */
	public function render_throwable( Throwable $e, OutputInterface $output ): void {
		$output->writeln(
			sprintf(
				'<error>Exception: %s</error>',
				$e->getMessage(),
			)
		);
	}
}
