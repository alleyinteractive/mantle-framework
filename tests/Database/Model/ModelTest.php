<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\TestCase;

use function Mantle\Support\Helpers\with;

/**
 * Test non-WordPress specific logic of the model
 */
class ModelTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();

		$_SERVER['__testable_model_boot'] = 0;
		$_SERVER['__boot_model_trait_to_test'] = 0;
		$_SERVER['__initialize_model_trait_to_test'] = 0;
	}
	public function test_boot_methods(): void {

		$this->assertSame( 0, $_SERVER['__testable_model_boot'] );
		$this->assertSame( 0, $_SERVER['__initialize_model_trait_to_test'] );
		$this->assertSame( 0, $_SERVER['__boot_model_trait_to_test'] );

		// Test the boot method.
		new Testable_Model();
		$this->assertSame( 1, $_SERVER['__testable_model_boot'] );
		$this->assertSame( 1, $_SERVER['__boot_model_trait_to_test'] );
		$this->assertSame( 1, $_SERVER['__initialize_model_trait_to_test'] );

		// Test the initialize method. Should be 2 for the initialize method only.
		new Testable_Model();
		$this->assertSame( 1, $_SERVER['__testable_model_boot'] );
		$this->assertSame( 1, $_SERVER['__boot_model_trait_to_test'] );
		$this->assertSame( 2, $_SERVER['__initialize_model_trait_to_test'] );
	}

	public function test_attributes_append(): void {
		$post = Testable_Post_For_Appending::find_or_fail( static::factory()->post->create_and_get() );

		$post->append( 'abstract' );

		$array = $post->to_array();

		$this->assertArrayHasKey( 'abstract', $array );
		$this->assertArrayHasKey( 'included_append', $array );
		$this->assertSame( 'value-to-compare', $array['abstract'] );
		$this->assertSame( 'included_append', $array['included_append'] );

		$post->set_appends( 'abstract' );
		$array = $post->to_array();

		$this->assertArrayHasKey( 'abstract', $array );
		$this->assertArrayNotHasKey( 'included_append', $array );
	}

	public function test_hidden_attribute(): void {
		$post = Testable_Post_For_Appending::find_or_fail(
			static::factory()->post->create_and_get( [ 'post_password' => 'password' ] )
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
		$this->assertSame(
			[
				'post_password' => 'password',
			],
			$array
		);

		$post->make_visible_if( static function() { return true; }, 'post_title' );
		$array = $post->to_array();

		$this->assertSame(
			[
				'post_title' => $post->title,
				'post_password' => 'password',
			],
			$array
		);
	}

	public function test_first_or_new(): void {
		$existing = static::factory()->post->create( [
			'title' => 'Original Title',
		] );

		$post = Testable_Post_For_Appending::first_or_new(
			[ 'id' => $existing ],
			[ 'title' => 'Updated title' ],
		);

		$this->assertSame( $existing, $post->id() );
		$this->assertSame( $existing, $post->id );
		$this->assertSame( 'Original Title', $post->title );

		$post_to_create = Testable_Post_For_Appending::first_or_new(
			[ 'title' => 'A title that does not exist' ],
			[ 'title' => 'New title' ],
		);

		$this->assertNull( $post_to_create->get( 'id' ) );
		$this->assertNull( with( $post_to_create->id ) );
		$this->assertFalse( $post_to_create->exists );
		$this->assertSame( 'New title', $post_to_create->title );
	}

	public function test_first_or_create(): void {
		$existing = static::factory()->post->create( [
			'title' => 'Original Title test_first_or_create',
		] );

		$post = Testable_Post_For_Appending::first_or_create(
			[ 'id' => $existing ],
			[ 'title' => 'Updated title test_first_or_create' ],
		);

		$this->assertSame( $existing, $post->id() );
		$this->assertSame( $existing, $post->id );
		$this->assertSame( 'Original Title test_first_or_create', $post->title );

		$post_to_create = Testable_Post_For_Appending::first_or_create(
			[ 'title' => 'A title that does not exist' ],
			[ 'title' => 'New title test_first_or_create' ],
		);

		$this->assertNotNull( with( $post_to_create->id ) );
		$this->assertTrue( $post_to_create->exists );
		$this->assertSame( 'New title test_first_or_create', $post_to_create->title );
	}

	public function test_update_or_create_by_post_title(): void {
		$existing = static::factory()->post->create( [ 'title' => 'Original title' ] );

		$post = Testable_Post_For_Appending::update_or_create(
			[ 'post_title' => 'A title that does not exist' ],
			[ 'content' => 'Created content', 'status' => 'publish' ],
		);

		$this->assertNotEquals( $existing, $post->id );
		$this->assertEquals( 'A title that does not exist', $post->title );
		$this->assertEquals( 'Original title', get_post_field( 'post_title', $existing ) );

		$found = Testable_Post_For_Appending::first_or_create( [ 'post_title' => 'A title that does not exist' ] );

		$this->assertEquals( $post->id, $found->id );
	}

	public function test_update_or_create(): void {
		$existing = static::factory()->post->create( [
			'title' => 'Original Title test_update_or_create',
		] );

		$post = Testable_Post_For_Appending::update_or_create(
			[ 'id' => $existing ],
			[ 'title' => 'Updated title test_update_or_create' ],
		);

		$this->assertSame( $existing, $post->id() );
		$this->assertSame( $existing, $post->id );
		$this->assertSame( 'Updated title test_update_or_create', $post->title );

		$post_to_create = Testable_Post_For_Appending::update_or_create(
			[ 'title' => 'A title that does not exist' ],
			[ 'title' => 'New title test_update_or_create' ],
		);

		$this->assertNotNull( with( $post_to_create->id ) );
		$this->assertTrue( $post_to_create->exists );
		$this->assertSame( 'New title test_update_or_create', $post_to_create->title );
	}
}

class Testable_Model extends Model {
	use Model_Trait_To_Test;

	public static function find( mixed $object ): ?static {
		return null;
	}

	public static function boot(): void {
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
	public static function boot_model_trait_to_test(): void {
		$_SERVER['__boot_model_trait_to_test']++;
	}

	public static function initialize_model_trait_to_test(): void {
		$_SERVER['__initialize_model_trait_to_test']++;
	}
}
