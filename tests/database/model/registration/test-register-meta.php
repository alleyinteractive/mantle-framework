<?php
namespace Mantle\Tests\Database\Model\Registration;

use Mantle\Contracts\Database\Registrable_Meta;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Registration\Register_Meta;
use Mantle\Testing\Framework_Test_Case;

class Test_Register_Meta extends Framework_Test_Case {
	public function test_register_meta() {
		$this->expectApplied( 'mantle_register_meta_default_args' )->once()->andReturn(
			[
				'object_subtype' => 'post',
				'show_in_rest'   => true,
				'single'         => true,
				'type'           => 'string',
			],
		);

		Testable_Post_Model_Meta::boot_if_not_booted();

		$this->assertTrue( registered_meta_key_exists( 'post', 'test_meta', 'post' ) );
		$this->assertFalse( registered_meta_key_exists( 'post', 'test_meta', 'page' ) );

		$keys = get_registered_meta_keys( 'post', 'post' );

		$this->assertTrue( $keys['test_meta']['single'] );
		$this->assertTrue( $keys['test_meta']['show_in_rest'] );
	}
}

class Testable_Post_Model_Meta extends Post implements Registrable_Meta {
	use Register_Meta;

	public static $object_name = 'post';

	protected static function boot() {
		static::register_meta( 'test_meta' );
	}
}
