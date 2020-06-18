<?php
/**
 * Job_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Framework\Console\Generator_Command;

/**
 * Queueable Job Generator Command
 */
class Job_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:job';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a job.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Jobs';

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
		return __DIR__ . '/stubs/job.stub';
	}
}
