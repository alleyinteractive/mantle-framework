<?php
/**
 * Seeder_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

/**
 * Seeder Generator
 */
class Seeder_Make_Command extends Stub_Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:seeder';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a seeder.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Database\Seeds';

	/**
	 * Get the stub file for the generator.
	 */
	public function get_file_stub(): string {
		$filename = 'seeder.stub';

		return __DIR__ . '/stubs/' . $filename;
	}

	/**
	 * Get the base path for the generated folder.
	 */
	protected function get_base_path(): string {
		return $this->container->get_base_path() . '/';
	}
}
