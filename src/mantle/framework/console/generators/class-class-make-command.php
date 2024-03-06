<?php
/**
 * Class_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Nette\PhpGenerator\PhpFile;

/**
 * Class Make Generator
 */
class Class_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:class';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a class.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{name}';

	/**
	 * Build the generated file.
	 *
	 * @param string $name Class name to generate.
	 * @return string
	 */
	public function get_generated_class( string $name ): string {
		$class_name     = $this->get_class_name( $name );
		$namespace_name = untrailingslashit( str_replace( '\\\\', '\\', $this->get_namespace( $name ) ) );

		$file = new PhpFile();

		$file
			->addComment( "$class_name class file.\n\n@package $namespace_name" )
			->addNamespace( $namespace_name )
			->addClass( $class_name )
			->addComment( "$class_name class." );

		return ( new Printer() )->printFile( $file );
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 */
	public function complete_synopsis( string $name ): void {
		$this->log(
			PHP_EOL . sprintf(
				'Class created [%s\%s]',
				$this->get_namespace( $name ),
				$this->get_class_name( $name )
			)
		);
	}
}
