<?php
/**
 * Factory_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use InvalidArgumentException;
use Mantle\Console\Command;
use Mantle\Support\Str;

/**
 * Factory Generator
 */
class Factory_Make_Command extends Stub_Generator_Command {
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
	protected $type = 'Database\Factory';

	/**
	 * Command synopsis.
	 *
	 * @var string
	 */
	protected $signature = '{name} {--model_type=} {--object_name=}';

	/**
	 * Get the stub file for the generator.
	 */
	public function get_file_stub(): string {
		$type = $this->option( 'model_type' );

		$filename = '';

		if ( 'post' === $type ) {
			$filename = 'factory-post.stub';
		} elseif ( 'term' === $type ) {
			$filename = 'factory-term.stub';
		} else {
			throw new InvalidArgumentException( 'Unknown factory type: ' . $type );
		}

		// Set the object type to use.
		$this->replacements->add(
			'{{ object_name }}',
			$this->option( 'object_name', $this->get_default_object_name() )
		);

		return __DIR__ . '/stubs/' . $filename;
	}

	/**
	 * Get the default object name.
	 */
	protected function get_default_object_name(): string {
		$class_name = $this->get_class_name( $this->argument( 'name' ) );
		return strtolower( str_replace( '_', '-', $class_name ) );
	}

	/**
	 * Get the default label.
	 */
	protected function get_default_label(): string {
		$class_name = str_replace( [ '_', '-' ], ' ', $this->get_class_name( $this->argument( 'name' ) ) );
		return ucwords( $class_name );
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 * @todo Edit or remove this.
	 */
	public function complete_synopsis( string $name ): void {
		$this->line( 'You can customize this factory by editing the file in "database/factory".' );
	}

	/**
	 * Get the folder location of the file.
	 *
	 * @param string $name Name to use.
	 */
	protected function get_folder_path( string $name ): string {
		return $this->container->get_base_path() . '/database/factory';
	}

	/**
	 * Get the location for the generated file.
	 *
	 * @param string $name Name to use.
	 */
	protected function get_file_path( string $name ): string {
		$parts    = explode( '\\', $name );
		$filename = array_pop( $parts );
		$filename = Str::slug( $filename );

		return $this->get_folder_path( $name ) . '/class-' . $filename . '-factory.php';
	}
}
