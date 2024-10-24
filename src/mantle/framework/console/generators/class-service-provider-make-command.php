<?php
/**
 * Service_Provider_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

/**
 * Service Provider Generator
 */
class Service_Provider_Make_Command extends Stub_Generator_Command {
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
	 */
	public function get_file_stub(): string {
		return __DIR__ . '/stubs/provider.stub';
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 */
	public function complete_synopsis( string $name ): void {
		$this->log(
			PHP_EOL . sprintf(
				'You can use this service provider by adding "%s\\%s::class" to the "providers" in "config/app.php".',
				$this->get_namespace( $name ),
				$this->get_class_name( $name )
			)
		);
	}
}
