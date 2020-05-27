<?php
namespace Mantle\Tests\Database\Model\Registration;

use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
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
}
