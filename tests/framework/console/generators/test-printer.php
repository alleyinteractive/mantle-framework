<?php
namespace Mantle\Tests\Framework\Console\Generators;

use Mantle\Filesystem\Filesystem;
use Mantle\Framework\Console\Generators\Printer;
use Nette\PhpGenerator\PhpFile;
use PHP_CodeSniffer\Runner;
use PHPUnit\Framework\TestCase;

class Test_Printer extends TestCase {
	protected static $temp_dir;
	protected static $argv;

	/**
	 * @var Filesystem
	 */
	protected static $filesystem;

	public static function setUpBeforeClass(): void {
		// Store argv for restoring after we're done.
		static::$argv = $_SERVER['argv'];

		// Ensure PHPCS is loaded.
		$reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
		$vendor_dir = dirname(dirname($reflection->getFileName()));

		require_once $vendor_dir . '/squizlabs/php_codesniffer/autoload.php';

		static::$temp_dir = MANTLE_PHPUNIT_INCLUDES_PATH . '/../../temp/';
		static::$filesystem = new Filesystem();

		static::$filesystem->ensure_directory_exists( static::$temp_dir );
		static::$filesystem->clean_directory( static::$temp_dir );
	}

	public static function tearDownAfterClass(): void {
		static::$filesystem->delete_directory( static::$temp_dir );
		$_SERVER['argv'] = static::$argv;
	}

	public function test_make_class() {
		$file = new PhpFile();

		$file
			->addComment( 'Example_Class file' )
			->addComment( '' )
			->addComment( '@package App' );

		$class = $file
			->addNamespace( 'Mantle' )
			->addClass( 'Example_Class' )
			->addComment( 'Example Class' );

		$class
			->addMethod( 'example_method' )
			->setReturnType( 'string' )
			->addComment( 'Example Method' )
			->addComment( '' )
			->addComment( '@param string $name Example param.' )
			->setBody( 'return \'123\';' )
			->addParameter( 'name', '123' )
			->setType( 'string' );

		$this->lint_code( $file, 'class-example-class' );
	}

	public function test_make_function() {
		$file = new PhpFile();

		$file
			->addComment( 'Example_Class file' )
			->addComment( '' )
			// For some reason these rules need to be disabled by the generated code
			// doesn't have any issues.
			->addComment( 'phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found' )
			->addComment( 'phpcs:disable Generic.WhiteSpace.ScopeIndent.Incorrect' )
			->addComment( '' )
			->addComment( '@package App' )
			->addNamespace( 'Mantle' )
			->addFunction( 'example_function' )
			->setReturnType( 'string' )
			->addComment( 'Example Method' )
			->addComment( '' )
			->addComment( '@param string $name Example param.' )
			->setBody( "// Example Method.\nreturn '123';" )
			->addParameter( 'name', '123' )
			->setType( 'string' );

		$this->lint_code( $file, 'functions-example' );
	}

	protected function lint_code( PhpFile $file, string $file_name ) {
		$code = ( new Printer() )->printFile( $file );
		$path = static::$temp_dir .  $file_name . '.php';

		static::$filesystem->put( $path, $code );

		$_SERVER['argv'] = [
			'phpcs',
			$path,
			'-vsn',
		];

		// tip: turn off the output buffering for help debugging!
		ob_start();
		$runner = new Runner();
		$exit_code = $runner->runPHPCS();
		ob_end_clean();

		$this->assertEquals( 0, $exit_code, 'phpcs exit code should be 0 for no errors.' );
	}

}
