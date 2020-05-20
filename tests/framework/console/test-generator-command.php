<?php
/**
 * Test_Generator_Command test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Console;

use Mantle\Framework\Application;
use Mantle\Framework\Config\Repository;
use Mantle\Framework\Console\Generator_Command;
use Mockery as m;

class Test_Generator_Command extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * @var Test_Generator_Public
	 */
	protected $command;

	public function setUp(): void {
		parent::setUp();

		$this->app = new Application( __DIR__ );
		$this->app->instance( 'config', new Repository( [ 'app' => [ 'namespace' => 'App' ] ] ) );
		$this->command = new Test_Generator_Public( $this->app );
	}

	public function test_generated_namespace() {
		$this->assertEquals( 'App\Provider', $this->command->get_namespace( 'Class_Name' ) );
		$this->assertEquals( 'App\Provider\Sub\Namespace', $this->command->get_namespace( 'Sub\Namespace\Class_Name' ) );

		// Test against a custom root namespace.
		$this->app['config']->set( 'app.namespace', 'Example\Namespace\To_Use' );
		$this->assertEquals( 'Example\Namespace\To_Use\Provider', $this->command->get_namespace( 'Class_Name' ) );
		$this->assertEquals( 'Example\Namespace\To_Use\Provider\Sub\Namespace', $this->command->get_namespace( 'Sub\Namespace\Class_Name' ) );
	}

	public function test_generated_class() {
		$this->assertEquals( 'Class_Name', $this->command->get_class_name( 'Class_Name' ) );
		$this->assertEquals( 'Class_Name', $this->command->get_class_name( 'Sub\Namespace\Class_Name' ) );

		// Test against a custom root namespace.
		$this->app['config']->set( 'app.namespace', 'Example\Namespace\To_Use' );
		$this->assertEquals( 'Class_Name', $this->command->get_class_name( 'Class_Name' ) );
		$this->assertEquals( 'Class_Name', $this->command->get_class_name( 'Sub\Namespace\Class_Name' ) );
	}

	public function test_folder_path() {
		$expected_app_path = __DIR__ . '/app/provider';
		$this->assertEquals( $expected_app_path, $this->command->get_folder_path( 'Class_Name' ) );
		$this->assertEquals( $expected_app_path . '/sub/namespace', $this->command->get_folder_path( 'Sub\Namespace\Class_Name' ) );

		// Test against a custom root namespace.
		$this->app['config']->set( 'app.namespace', 'Example\Namespace\To_Use' );
		$this->assertEquals( $expected_app_path, $this->command->get_folder_path( 'Class_Name' ) );
		$this->assertEquals( $expected_app_path . '/sub/namespace', $this->command->get_folder_path( 'Sub\Namespace\Class_Name' ) );
	}

	public function test_file_path() {
		$expected_app_path = __DIR__ . '/app/provider';
		$this->assertEquals( $expected_app_path . '/class-example-name.php', $this->command->get_file_path( 'Example_Name' ) );
		$this->assertEquals( $expected_app_path . '/sub/namespace/class-example-name.php', $this->command->get_file_path( 'Sub\Namespace\Example_Name' ) );

		// Test against a custom root namespace.
		$this->app['config']->set( 'app.namespace', 'Example\Namespace\To_Use' );
		$this->assertEquals( $expected_app_path . '/class-example-name.php', $this->command->get_file_path( 'Example_Name' ) );
		$this->assertEquals( $expected_app_path . '/sub/namespace/class-example-name.php', $this->command->get_file_path( 'Sub\Namespace\Example_Name' ) );
	}
}

/**
 * Generator Command with protected methods exposed to the public.
 */
class Test_Generator_Public extends Generator_Command {
	protected $type = 'Provider';

	public function get_file_stub(): string {
		return file_get_contents( __DIR__ . '/test.stub' );
	}

	public function get_class_name( string $name ): string {
		return parent::get_class_name( $name );
	}

	public function get_namespace( string $name ): string {
		return parent::get_namespace( $name );
	}

	public function get_folder_path( string $name ): string {
		return parent::get_folder_path( $name );
	}

	public function get_file_path( string $name ): string {
		return parent::get_file_path( $name );
	}
}
