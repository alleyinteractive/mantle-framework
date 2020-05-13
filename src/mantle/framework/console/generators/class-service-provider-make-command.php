<?php
/**
 * Service_Provider_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Framework\Console\Generator_Command;

/**
 * Service Provider Generator
 */
class Service_Provider_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:provider';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a service provider.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Providers';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	public function get_file_stub(): string {
		return __DIR__ . '/stubs/provider.stub';
	}

	/**
	 * Service Provider Generator Command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		parent::handle( $args, $assoc_args );

		list( $name ) = $args;

		$this->log( '' );
		$this->log(
			sprintf(
				'You can use this service provider by adding "%s::class" to the "providers" in "config/app.php".',
				$this->get_namespace( $name ) . '\\' . $this->get_class_name( $name )
			)
		);
	}
}
