<?php
/**
 * Command_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Support\Str;

/**
 * Command Generator
 */
class Command_Make_Command extends Stub_Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:command';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a command.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Console';

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
		$filename = 'command.stub';

		$this->replacements->add(
			'{{ command_name }}',
			str_replace( '__', '_', Str::snake( $this->get_class_name( $this->get_arg( 0 ) ) ) )
		);

		return __DIR__ . '/stubs/' . $filename;
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 */
	public function complete_synopsis( string $name ) {
		$this->log(
			PHP_EOL . sprintf(
				'This command should be automatically registered for you. If it is not you can register this command by adding "%s\\%s::class" to the "$commands" property in "app/console/class-kernel.php".',
				$this->get_namespace( $name ),
				$this->get_class_name( $name )
			)
		);
	}
}
