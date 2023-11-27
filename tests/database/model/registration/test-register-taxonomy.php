<?php
namespace Mantle\Tests\Database\Model\Registration;

use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Registration\Register_Taxonomy;
use Mantle\Testing\Framework_Test_Case;
use Mockery as m;

class Test_Register_Taxonomy extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();
		remove_all_actions( 'init' );
	}

	protected function tearDown(): void {
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

		$mock->register_object();

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

		$mock->register_object();

		// Allow the taxonomy to be registered.
		do_action( 'init' );

		$this->assertTrue( taxonomy_exists( $taxonomy ) );
	}
}
