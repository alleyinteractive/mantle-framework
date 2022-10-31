<?php
/**
 * Seeder class file.
 *
 * @package Mantle
 */

namespace Mantle\Database;

use InvalidArgumentException;
use Mantle\Console\Command;
use Mantle\Contracts\Container;
use Mantle\Support\Arr;

/**
 * Base Database Seeder
 */
abstract class Seeder {
	/**
	 * The container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * The console command instance.
	 *
	 * @var Command
	 */
	protected $command;

	/**
	 * Seed the given connection from the given path.
	 *
	 * @param string[]|string $class Seed to run.
	 * @param bool            $silent Flag if the seed should be silent.
	 * @return static
	 */
	public function call( $class, bool $silent = false ) {
		$classes = Arr::wrap( $class );

		foreach ( $classes as $class ) {
			$seeder = $this->resolve( $class );
			$name   = get_class( $seeder );

			if ( ! $silent && isset( $this->command ) ) {
				$this->command->line( "Seeding: {$name}" );
			}

			$start_time = microtime( true );

			$seeder->__invoke();

			$run_time = round( microtime( true ) - $start_time, 2 );

			if ( ! $silent && isset( $this->command ) ) {
				$this->command->line( "Seeded: {$name} ({$run_time} seconds)" );
			}
		}

		return $this;
	}

	/**
	 * Resolve an instance of the given seeder class.
	 *
	 * @param  string $class
	 * @return Seeder
	 */
	protected function resolve( $class ) {
		if ( isset( $this->container ) ) {
			$instance = $this->container->make( $class );

			$instance->set_container( $this->container );
		} else {
			$instance = new $class();
		}

		return $instance;
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param Container $container
	 * @return static
	 */
	public function set_container( Container $container ) {
		$this->container = $container;

		return $this;
	}

	/**
	 * Set the command instance instance.
	 *
	 * @param Command $command
	 * @return static
	 */
	public function set_command( Command $command ) {
		$this->command = $command;

		return $this;
	}

	/**
	 * Run the database seeds.
	 *
	 * @return mixed
	 *
	 * @throws InvalidArgumentException Thrown on bad seeder class.
	 */
	public function __invoke() {
		if ( ! method_exists( $this, 'run' ) ) {
			throw new InvalidArgumentException( 'Method [run] missing from ' . get_class( $this ) );
		}

		return isset( $this->container )
			? $this->container->call( [ $this, 'run' ] )
			: $this->run();
	}
}
