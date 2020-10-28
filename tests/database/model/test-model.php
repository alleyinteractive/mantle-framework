<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Database\Model\Model;
use PHPUnit\Framework\TestCase;

/**
 * Test non-WordPress specific logic of the model
 */
class Test_Model extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		$_SERVER['__testable_model_boot'] = 0;
		$_SERVER['__boot_model_trait_to_test'] = 0;
		$_SERVER['__initialize_model_trait_to_test'] = 0;
	}
	public function test_boot_methods() {

		$this->assertEquals( 0, $_SERVER['__testable_model_boot'] );
		$this->assertEquals( 0, $_SERVER['__initialize_model_trait_to_test'] );
		$this->assertEquals( 0, $_SERVER['__boot_model_trait_to_test'] );

		// Test the boot method.
		new Testable_Model();
		$this->assertEquals( 1, $_SERVER['__testable_model_boot'] );
		$this->assertEquals( 1, $_SERVER['__boot_model_trait_to_test'] );
		$this->assertEquals( 1, $_SERVER['__initialize_model_trait_to_test'] );

		// Test the initialize method. Should be 2 for the initialize method only.
		new Testable_Model();
		$this->assertEquals( 1, $_SERVER['__testable_model_boot'] );
		$this->assertEquals( 1, $_SERVER['__boot_model_trait_to_test'] );
		$this->assertEquals( 2, $_SERVER['__initialize_model_trait_to_test'] );
	}
}

class Testable_Model extends Model {
	use Model_Trait_To_Test;

	public static function find( $object ) { }

	public static function boot() {
		$_SERVER['__testable_model_boot']++;
	}
}

trait Model_Trait_To_Test {
	public static function boot_model_trait_to_test() {
		$_SERVER['__boot_model_trait_to_test']++;
	}

	public static function initialize_model_trait_to_test() {
		$_SERVER['__initialize_model_trait_to_test']++;
	}
}
