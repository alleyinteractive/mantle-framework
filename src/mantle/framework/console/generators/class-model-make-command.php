<?php
/**
 * Model_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use InvalidArgumentException;
use Mantle\Console\Command;

/**
 * Model Generator
 *
 * @todo Add support for generating a controller, migration, and seed in addition to the model.
 */
class Model_Make_Command extends Stub_Generator_Command {
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
	 * Command signature.
	 *
	 * @var string|array
	 */
	protected $signature = '{name} {--model_type=: post, term} {--registrable} {--object_name=} {--label_singular=} {--label_plural=}';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	public function get_file_stub(): string {
		$type        = $this->option( 'model_type' );
		$registrable = $this->option( 'registrable', false );

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
			throw new InvalidArgumentException( 'Unknown model type: ' . $type, true );
		}

		// Set the object type to use.
		$this->replacements->add(
			'{{ object_name }}',
			$this->option( 'object_name', $this->get_default_object_name() )
		);

		$inflector      = $this->inflector();
		$default_label  = $this->get_default_label();
		$singular_label = $inflector->singularize( $default_label )[0] ?? $default_label;
		$plural_label   = $inflector->pluralize( $singular_label )[0] ?? $default_label;

		$this->replacements->add(
			'{{ label_singular }}',
			$this->option( 'label_singular', $singular_label )
		);

		$this->replacements->add(
			'{{ label_plural }}',
			$this->option( 'label_plural', $plural_label )
		);

		return __DIR__ . '/stubs/' . $filename;
	}

	/**
	 * Get the default object name.
	 *
	 * @return string
	 */
	protected function get_default_object_name(): string {
		$class_name = $this->get_class_name( $this->argument( 'name' ) );
		return strtolower( str_replace( '_', '-', $class_name ) );
	}

	/**
	 * Get the default label.
	 *
	 * @return string
	 */
	protected function get_default_label(): string {
		$class_name = str_replace( [ '_', '-' ], ' ', $this->get_class_name( $this->argument( 'name' ) ) );
		return ucwords( $class_name );
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 */
	public function complete_synopsis( string $name ) {
		if ( ! $this->option( 'registrable', false ) ) {
			return;
		}

		// Run the model discovery command.
		$this->call( 'mantle model:discover' );
		$this->log( 'Your model should automatically be registered with WordPress.' );
	}
}
