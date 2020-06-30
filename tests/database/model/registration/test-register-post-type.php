<?php
namespace Mantle\Tests\Database\Model\Registration;

use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Framework\Contracts\Database\Registrable_Fields;
use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
use Mantle\Framework\Database\Model\Registration\Register_Rest_Fields;
use WP_UnitTestCase;
use Mockery as m;

class Test_Register_Post_Type extends WP_UnitTestCase {
	public function tearDown() {
		parent::tearDown();
		m::close();
	}

	public function test_register_post_type() {
		$post_type = 'test-post-type';

		$this->assertFalse( post_type_exists( $post_type ) );

		$mock = m::mock( Register_Post_Type::class );
		$mock->shouldReceive( 'get_object_name' )->andReturn( $post_type );
		$mock->shouldReceive( 'get_registration_args' )->andReturn(
			[
				'public' => true,
			]
		);

		$mock->register();

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
			[
				'public' => true,
			]
		);

		$mock->register();

		// Allow the post type to be registered.
		do_action( 'init' );

		$this->assertTrue( post_type_exists( $post_type ) );
	}

	public function test_field_registration() {
		$post_type = 'test-field-post-type';

		Testable_Post_Type_With_Fields::$object_name = $post_type;

		// Testable_Post_Type_With_Fields::boot();
		Testable_Post_Type_With_Fields::register();
		Testable_Post_Type_With_Fields::register_fields();

		// $mock = m::mock( Register_Post_Type::class );
		// $mock->shouldReceive( 'get_object_name' )->andReturn( $post_type );
		// $mock->shouldReceive( 'get_registration_args' )->andReturn(
		// 	[
		// 		'public'       => true,
		// 		'show_in_rest' =>  true,
		// 	]
		// );

		// $mock->register();

		// Allow the post type to be registered.
		do_action( 'init' );
		do_action( 'rest_api_init' );

		$this->assertTrue( post_type_exists( $post_type ) );
	}
}

class Testable_Post_Type_With_Fields extends Post implements Registrable, Registrable_Fields {
	use Register_Post_Type, Register_Rest_Fields;

	public static $object_name = 'test-field-post-type';

	public static function boot() {
		var_dump('boot');
	}

	public static function get_registration_args(): array {
		return [
				'public'       => true,
				'show_in_rest' =>  true,
		];
	}
}
