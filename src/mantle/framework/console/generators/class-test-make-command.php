<?php
/**
 * Test_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

/**
 * Test Case Generator
 */
class Test_Make_Command extends Stub_Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:test';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a test case.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Tests';

	/**
	 * Prefix for the file.
	 *
	 * @var string
	 */
	protected $prefix = 'test-';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{name}';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	public function get_file_stub(): string {
		return __DIR__ . '/stubs/test.stub';
	}

	/**
	 * Get the base path for the generated folder.
	 *
	 * @return string
	 */
	protected function get_base_path(): string {
		return base_path( '/' );
	}
}
