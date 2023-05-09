<?php
/**
 * Generator_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Console\Command;

/**
 * Generator Generator
 */
class Generator_Make_Command extends Stub_Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:generator';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a generator.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Console\Generators';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{name} {type}';

	/**
	 * Generator Command.
	 *
	 * @todo Replace with a filesystem abstraction.
	 */
	public function handle() {
		// Prevent command being run in non-local environments.
		if ( 'local' !== $this->container->environment() ) {
			$this->error( 'Generator cannot be used outside of local environment.' );
			return Command::FAILURE;
		}

		$name = $this->argument( 'name' );
		$type = $this->argument( 'type' );

		$path = $this->get_folder_path( $name );

		// Ensure the folder path exists.
		if ( ! is_dir( $path ) && ! mkdir( $path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $path );
			return Command::FAILURE;
		}

		$file_path = $this->get_file_path( $name . '-make-command' );
		if ( file_exists( $file_path ) ) {
			$this->error( $this->type . ' already exists: ' . $file_path );
			return Command::FAILURE;
		}

		// Write the generated class to disk.
		if ( false === file_put_contents( $file_path, $this->get_generated_class( $name ) ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			$this->error( 'Error writing to ' . $file_path );
			return Command::FAILURE;
		}

		// Create the stub file for the generated generator.
		$stub_path = $this->get_base_path() . 'console/generators/stubs';
		if ( ! is_dir( $stub_path ) && ! mkdir( $stub_path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $stub_path );
			return Command::FAILURE;
		}

		if ( ! copy( __DIR__ . '/stubs/stub.stub', $stub_path . '/' . $name . '.stub' ) ) {
			$this->error( 'Error copying stub file to ' . $stub_path );
			return Command::FAILURE;
		}

		$this->log( $this->type . ' created successfully: ' . $file_path );

		$this->complete_synopsis( $name );

		return Command::SUCCESS;
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	public function get_file_stub(): string {
		$filename = 'generator.stub';
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
				'You can auto-register this generator by adding "%s\\%s::class" to the "commands" in "app/console/class-kernel.php".',
				$this->get_namespace( $name ),
				$this->get_class_name( $name )
			)
		);
		$this->log( 'You can customize the template this generator uses by editing its stub file in "app/console/generators/stubs/"' );
	}
}
