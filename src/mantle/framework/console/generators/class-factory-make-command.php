<?php
/**
 * Factory_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Framework\Console\Generator_Command;

/**
 * Factory Generator
 */
class Factory_Make_Command extends Generator_Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:factory';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a factory.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Factory';

	/**
	 * Command synopsis.
	 *
	 * @var string|array
	 */
	protected $synopsis = [
		[
			'description' => 'Factory name',
			'name'        => 'name',
			'optional'    => false,
			'type'        => 'positional',
		],
		[
			'description' => 'Model Type',
			'name'        => 'model_type',
			'optional'    => false,
			'type'        => 'assoc',
			'options'     => [ 'post', 'term' ],
		],
	];

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	public function get_file_stub(): string {
		$type = $this->get_flag( 'model_type' );

		$filename = '';

		if ( 'post' === $type ) {
			$filename = 'factory.stub';
		} else {
			$this->error( 'Unknown factory type: ' . $type, true );
		}

		return __DIR__ . '/stubs/' . $filename;
	}

	/**
	 * Get the default object name.
	 *
	 * @return string
	 */
	protected function get_default_object_name(): string {
		$class_name = $this->get_class_name( $this->get_arg( 0 ) );
		return strtolower( str_replace( '_', '-', $class_name ) );
	}

	/**
	 * Get the default label.
	 *
	 * @return string
	 */
	protected function get_default_label(): string {
		$class_name = str_replace( [ '_', '-' ], ' ', $this->get_class_name( $this->get_arg( 0 ) ) );
		return ucwords( $class_name );
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 * @todo Edit or remove this.
	 */
	public function synopsis( string $name ) {
		$this->log(
			PHP_EOL . sprintf(
				'You can auto-register this model by adding "%s\\%s::class" to the "register" in "config/models.php".',
				$this->get_namespace( $name ),
				$this->get_class_name( $name )
			)
		);
	}
}
