<?php
/**
 * Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Console;

use InvalidArgumentException;
use Mantle\Support\Traits\Macroable;
use Symfony\Component\Console\Command\Command as Symfony_Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command for Service Providers
 */
abstract class Command extends Symfony_Command {
	use Concerns\Interacts_With_IO,
		Macroable;

	/**
	 * Prefix for the command.
	 *
	 * @var string
	 */
	public const PREFIX = 'mantle';

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = '';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature;

	/**
	 * The command's help text.
	 *
	 * @var string
	 */
	protected string $help;

	/**
	 * Container instance.
	 *
	 * @var \Mantle\Contracts\Application
	 */
	protected \Mantle\Contracts\Application $container;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Infer the name from the signature.
		if ( ! empty( $this->signature ) ) {
			$this->set_definition_from_signature();
		} else {
			parent::__construct( $this->name );
		}

		$this->setDescription( $this->short_description ?: $this->description );

		if ( ! empty( $this->help ) ) {
			$this->setHelp( $this->help );
		}
	}

	/**
	 * Setup the definition from the signature.
	 */
	protected function set_definition_from_signature() {
		// Prefix the signature with the name if defined separately.
		if ( ! empty( $this->name ) && 0 !== strpos( $this->signature, $this->name ) ) {
			$this->signature = $this->name . ' ' . $this->signature;
		}

		[ $this->name, $arguments, $options ] = Parser::parse( $this->signature );

		parent::__construct( $this->name );

		// After parsing the signature we will spin through the arguments and options
		// and set them on this command. These will already be changed into proper
		// instances of these "InputArgument" and "InputOption" Symfony classes.
		$this->getDefinition()->addArguments( $arguments );
		$this->getDefinition()->addOptions( $options );
	}

	/**
	 * Getter for the command name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Runs the command.
	 *
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::run()
	 *
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 */
	public function run( InputInterface $input, OutputInterface $output ): int {
		$this->output = $this->container->make(
			Output_Style::class,
			[
				'input'  => $input,
				'output' => $output,
			]
		);

		return parent::run( $this->input = $input, $this->output );
	}

	/**
	 * Execute the console command.
	 *
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$this->set_input( $input );
		$this->set_output( $output );

		$method = method_exists( $this, 'handle' ) ? 'handle' : '__invoke';

		return (int) $this->container->call( [ $this, $method ] );
	}

	/**
	 * Run another command.
	 *
	 * @param string          $command Command to run.
	 * @param array           $options Options for the command.
	 * @param OutputInterface $output Output interface.
	 * @return int|mixed
	 *
	 * @throws InvalidArgumentException Thrown on invalid command.
	 */
	public function call( string $command, array $options = [], OutputInterface $output = null ) {
		if ( 0 === strpos( $command, static::PREFIX . ' ' ) ) {
			$command = substr( $command, strlen( static::PREFIX ) + 1 );

			// Attempt to resolve the command from the container and run it.
			$command = $this->getApplication()->find( $command );

			return $command->run( new ArrayInput( $options ), $output ?: new ConsoleOutput() );
		}

		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			throw new InvalidArgumentException( 'Unable to proxy to WP-CLI when not running in WP-CLI mode.' );
		}

		return \WP_CLI::runcommand( $command, $options );
	}

	/**
	 * Set the application container.
	 *
	 * @param \Mantle\Contracts\Application $container Application container.
	 */
	public function set_container( \Mantle\Contracts\Application $container ): void {
		$this->container = $container;
	}

	/**
	 * Retrieve the application container.     */
	public function get_container(): \Mantle\Contracts\Application {
		return $this->container;
	}
}
