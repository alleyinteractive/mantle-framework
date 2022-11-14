<?php
/**
 * Application class file
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Console\Application as Console_Application_Contract;
use Mantle\Contracts\Container;
use Mantle\Support\Arr;
use Symfony\Component\Console\Application as Console_Application;
use Symfony\Component\Console\Command\Command as Symfony_Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

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
	 * @return void
	 */
	public static function starting( Closure $callback ) {
		static::$bootstrappers[] = $callback;
	}

	/**
	 * Forget all of the bootstrappers for the application.
	 *
	 * @return void
	 */
	public static function forget_bootstrappers() {
		static::$bootstrappers = [];
	}

	/**
	 * Constructor.
	 *
	 * @param Container $container
	 */
	public function __construct( protected Container $container ) {
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
	 * @return int
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
	 * @return CommandTester
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
	 * @return static
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
	 * @return static
	 */
	public function resolve_commands( $commands ): static {
		$commands = Arr::wrap( $commands );

		foreach ( $commands as $command ) {
			$this->resolve( $command );
		}

		return $this;
	}
}
