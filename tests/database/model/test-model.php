<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\TestCase;

/**
 * Test non-WordPress specific logic of the model
 */
class Test_Model extends Framework_Test_Case {
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

	public function test_attributes_append() {
		$post = Testable_Post_For_Appending::find( static::factory()->post->create() );

		$post->append( 'abstract' );

		$array = $post->to_array();

		$this->assertArrayHasKey( 'abstract', $array );
		$this->assertArrayHasKey( 'included_append', $array );
		$this->assertEquals( 'value-to-compare', $array['abstract'] );
		$this->assertEquals( 'included_append', $array['included_append'] );

		$post->set_appends( 'abstract' );
		$array = $post->to_array();

		$this->assertArrayHasKey( 'abstract', $array );
		$this->assertArrayNotHasKey( 'included_append', $array );
	}

	public function test_hidden_attribute() {
		$post = Testable_Post_For_Appending::find(
			static::factory()->post->create( [ 'post_password' => 'password' ] )
		);

		$array = $post->to_array();
		$this->assertArrayHasKey( 'post_title', $array );
		$this->assertArrayNotHasKey( 'post_password', $array );

		$post->set_visible( 'post_password' );
		$post->set_hidden( 'post_title', 'included_append' );
		$array = $post->to_array();

		$this->assertArrayNotHasKey( 'post_title', $array );
		$this->assertArrayNotHasKey( 'included_append', $array );
		$this->assertArrayHasKey( 'post_password', $array );
		$this->assertEquals(
			[
				'post_password' => 'password',
			],
			$array
		);

		$post->make_visible_if( function() { return true; }, 'post_title' );
		$array = $post->to_array();

		$this->assertEquals(
			[
				'post_title' => $post->title,
				'post_password' => 'password',
			],
			$array
		);
	}
}

class Testable_Model extends Model {
	use Model_Trait_To_Test;

	public static function find( $object ) { }

	public static function boot() {
		$_SERVER['__testable_model_boot']++;
	}
}

class Testable_Post_For_Appending extends Post {
	public static $object_name = 'post';

	protected $appends = [ 'included_append' ];

	public function get_included_append_attribute(): string {
		return 'included_append';
	}

	public function get_abstract_attribute(): string {
		return 'value-to-compare';
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
