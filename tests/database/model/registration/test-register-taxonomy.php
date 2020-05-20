<?php
namespace Mantle\Tests\Database\Model\Registration;

use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Registration\Register_Taxonomy;
use WP_UnitTestCase;
use Mockery as m;

class Test_Register_Taxonomy extends WP_UnitTestCase {
	public function tearDown() {
		parent::tearDown();
		m::close();
	}

	public function test_register_taxonomy() {
		$taxonomy = 'test-taxonomy';

		$this->assertFalse( taxonomy_exists( $taxonomy ) );

		$mock = m::mock( Register_Taxonomy::class );
		$mock->shouldReceive( 'get_object_name' )->andReturn( $taxonomy );
		$mock->shouldReceive( 'get_registration_args' )->andReturn(
			[
				'public' => true,
			]
		);

		$mock->register();

		// Allow the taxonomy to be registered.
		do_action( 'init' );

		$this->assertTrue( taxonomy_exists( $taxonomy ) );
	}

	public function test_invalid_taxonomy() {
		$taxonomy = 'category';

		$this->expectException( Model_Exception::class );
		$this->expectExceptionMessage( 'Unable to register taxonomy (taxonomy already exists): ' . $taxonomy );
		$this->assertTrue( taxonomy_exists( $taxonomy ) );

		$mock = m::mock( Register_Taxonomy::class );
		$mock->shouldReceive( 'get_object_name' )->andReturn( $taxonomy );
		$mock->shouldReceive( 'get_registration_args' )->andReturn(
			[
				'public' => true,
			]
		);

		$mock->register();

		// Allow the taxonomy to be registered.
		do_action( 'init' );

		$this->assertTrue( taxonomy_exists( $taxonomy ) );
	}
}
