<?php
/**
 * Closure_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Closure;
use ReflectionFunction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Closure Command
 */
class Closure_Command extends Command {
	/**
	 * Constructor.
	 *
	 * @param string  $signature
	 * @param Closure $callback
	 */
	public function __construct( string $signature, protected Closure $callback ) {
		$this->signature = $signature;

		$this->set_definition_from_signature();
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

		$inputs = array_merge( $input->getArguments(), $input->getOptions() );

		$parameters = [];

		foreach ( ( new ReflectionFunction( $this->callback ) )->getParameters() as $parameter ) {
			if ( isset( $inputs[ $parameter->getName() ] ) ) {
				$parameters[ $parameter->getName() ] = $inputs[ $parameter->getName() ];
			}
		}

		return (int) $this->container->call(
			$this->callback->bindTo( $this, $this ),
			$parameters,
		);
	}

	/**
	 * Set the description for the command.
	 *
	 * @param string $description Command description.
	 * @return static
	 */
	public function describe( string $description ): static {
		$this->setDescription( $description );

		return $this;
	}
}
