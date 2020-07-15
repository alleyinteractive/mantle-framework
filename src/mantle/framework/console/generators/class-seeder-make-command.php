<?php
/**
 * Seeder_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Framework\Console\Generator_Command;

/**
 * Seeder Generator
 */
class Seeder_Make_Command extends Generator_Command {
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
	 * Command synopsis.
	 *
	 * @var string|array
	 */
	protected $synopsis = [
		[
			'description' => 'Class name',
			'name'        => 'name',
			'optional'    => false,
			'type'        => 'positional',
		],
	];

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	public function get_file_stub(): string {
		$filename = 'seeder.stub';

		return __DIR__ . '/stubs/' . $filename;
	}

	/**
	 * Get the base path for the generated folder.
	 *
	 * @return string
	 */
	protected function get_base_path(): string {
		return $this->app->get_base_path() . '/';
	}
}
