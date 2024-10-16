<?php
/**
 * Middleware_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

/**
 * Middleware Generator
 */
class Middleware_Make_Command extends Stub_Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:middleware';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a middleware class.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Http\Middleware';

	/**
	 * Get the stub file for the generator.
	 */
	public function get_file_stub(): string {
		$filename = 'middleware.stub';

		return __DIR__ . '/stubs/' . $filename;
	}
}
