<?php
namespace Mantle\Tests\Database\Model\Registration;

use Mantle\Contracts\Database\Registrable;
use Mantle\Contracts\Database\Registrable_Fields;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Registration\Register_Post_Type;
use Mantle\Database\Model\Registration\Register_Rest_Fields;
use Mockery as m;
use Mantle\REST_API\Registered_REST_Field;
use Mantle\Testing\Framework_Test_Case;

class Test_Register_Post_Type extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();
		remove_all_actions( 'init' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		m::close();

		unregister_post_type( 'test-post-type' );
		remove_all_actions( 'rest_api_init' );
	}

	public function test_register_post_type() {
		$post_type = 'test-post-type';

		$this->assertFalse( post_type_exists( $post_type ) );

		$mock = m::mock( Register_Post_Type::class );
		$mock->shouldReceive( 'get_object_name' )->andReturn( $post_type );
		$mock->shouldReceive( 'get_registration_args' )->andReturn(
			array(
				'public' => true,
			)
		);

		$mock->boot_register_post_type();

		// Allow the post type to be registered.
		do_action( 'init' );

		$this->assertTrue( post_type_exists( $post_type ) );
	}

	public function test_invalid_register_post_type() {
		$post_type = 'post';

		$this->expectException( Model_Exception::class );
		$this->expectExceptionMessage( 'Unable to register post type (post type already exists): ' . $post_type );
		$this->assertTrue( post_type_exists( $post_type ) );

		$mock = m::mock( Register_Post_Type::class );
		$mock->shouldReceive( 'get_object_name' )->andReturn( $post_type );
		$mock->shouldReceive( 'get_registration_args' )->andReturn(
			array(
				'public' => true,
			)
		);

		$mock->boot_register_post_type();

		// Allow the post type to be registered.
		do_action( 'init' );

		$this->assertTrue( post_type_exists( $post_type ) );
	}

	public function test_field_registration() {
		$this->assertNull( Registered_REST_Field::get_instance( 'post', 'testable-field' ) );

		// Invoke a new instance of the model which will allow it to boot.
		new Testable_Post_Type_With_Fields();

		// Ensure the post type is registered.
		$this->assertTrue( post_type_exists( 'post' ) );

		// Register the Rest API Fields.
		do_action( 'rest_api_init' );

		// Ensure the expected fields were registered.
		$field = Registered_REST_Field::get_instance( 'post', 'testable-field' );
		$this->assertNotNull( $field );
		$this->assertEquals( 'Testable field description', $field->schema['description'] );

		$request = new \WP_REST_Request();
		$this->assertEquals( 'testable_get_callback', $field->get_callback( 'post', 'testable-field', $request, 'post' ) );

		// Ensure the callback is dynamic and will return a different value every time.
		$_SERVER['__testable_get_callback'] = 'updated_value';
		$this->assertEquals( 'updated_value', $field->get_callback( 'post', 'testable-field', $request, 'post' ) );

		// Verify the update callback.
		$this->assertEquals( 'testable_update_callback', $field->update_callback( 'test', 'post', 'testable-field', $request, 'post' ) );
	}
}

class Testable_Post_Type_With_Fields extends Post implements Registrable_Fields {
	use Register_Rest_Fields;

	public static $object_name = 'post';

	public static function get_registration_args(): array {
		return array(
			'public'       => true,
			'show_in_rest' => true,
		);
	}

	public static function boot() {
		parent::boot();

		static::register_field( 'testable-field', __NAMESPACE__ . '\testable_get_callback' )
			->set_description( 'Testable field description' )
			->set_update_callback( __NAMESPACE__ . '\testable_update_callback' );
	}
}

function testable_get_callback() {
	return $_SERVER['__testable_get_callback'] ?? 'testable_get_callback';
}

function testable_update_callback() {
	return $_SERVER['__testable_update_callback'] ?? 'testable_update_callback';
}
