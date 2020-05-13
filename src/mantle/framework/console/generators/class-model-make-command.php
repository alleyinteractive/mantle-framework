<?php
/**
 * Model_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Framework\Console\Generator_Command;

/**
 * Model  Generator
 */
class Model_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:model';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a model.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Models';

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
			'description' => 'Model Type',
			'name'        => 'model_type',
			'optional'    => false,
			'type'        => 'assoc',
			'options'     => [ 'post', 'term' ],
		],
		[
			'description' => 'Flag if a model is registrable',
			'name'        => 'registrable',
			'optional'    => true,
			'type'        => 'flag',
		],
		[
			'description' => 'Object name to use, defaults to inferring from the class name',
			'name'        => 'object_name',
			'optional'    => true,
			'type'        => 'flag',
		],
		[
			'description' => 'Singular Label to use',
			'name'        => 'label_singular',
			'optional'    => true,
			'type'        => 'flag',
		],
		[
			'description' => 'Plural Label to use',
			'name'        => 'label_plural',
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
		$type        = $this->get_flag( 'model_type' );
		$registrable = $this->get_flag( 'registrable', false );

		$filename = '';

		if ( 'post' === $type ) {
			$filename = 'model-post.stub';

			if ( $registrable ) {
				$filename = 'model-post-registrable.stub';
			}
		} elseif ( 'term' === $type ) {
			$filename = 'model-term.stub';

			if ( $registrable ) {
				$filename = 'model-term-registrable.stub';
			}
		} else {
			$this->error( 'Unknown model type: ' . $type, true );
		}

		// Set the object type to use.
		$this->replacements->add(
			'{{ object_name }}',
			$this->get_flag( 'object_name', $this->get_default_object_name() )
		);

		$this->replacements->add(
			'{{ label_singular }}',
			$this->get_flag( 'label_singular', $this->get_label_singular() )
		);

		$this->replacements->add(
			'{{ label_plural }}',
			$this->get_flag( 'label_plural', $this->get_label_plural() )
		);

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
	 * Get the singular label.
	 *
	 * @return string
	 */
	protected function get_label_singular(): string {
		$class_name = $this->get_class_name( $this->get_arg( 0 ) );
		return $class_name;
	}

	/**
	 * Get the plural label.
	 *
	 * @return string
	 */
	protected function get_label_plural(): string {
		$class_name = $this->get_class_name( $this->get_arg( 0 ) );
		return $class_name;
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 */
	public function synopsis( string $name ) {
		if ( ! $this->get_flag( 'registrable', false ) ) {
			return;
		}

		$this->log(
			PHP_EOL . sprintf(
				'You can auto-register this model by adding "%s\\%s::class" to the "register" in "config/models.php".',
				$this->get_namespace( $name ),
				$this->get_class_name( $name )
			)
		);
	}
}
