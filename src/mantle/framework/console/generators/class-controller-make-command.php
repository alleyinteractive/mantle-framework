<?php
/**
 * Controller_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Framework\Console\Generator_Command;

/**
 * Controller Generator
 */
class Controller_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:controller';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a controller.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Http\Controller';

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
		[
			'description' => 'Invokable controller',
			'name'        => 'invokable',
			'optional'    => true,
			'type'        => 'flag',
		],
	];

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	public function get_file_stub(): string {
		$filename = 'controller.stub';

		if ( $this->get_flag( 'invokable' ) ) {
			$filename = 'controller-invokable.stub';
		}

		return __DIR__ . '/stubs/' . $filename;
	}
}
