<?php
namespace Mantle\Tests\Database\Model\Registration;

use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Registration\Register_Meta;
use Mockery as m;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Register_Meta extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();
		remove_all_actions( 'init' );
	}

	public function test_register_meta() {
		Testable_Post_Model_Meta::boot_if_not_booted();

		do_action( 'init' );

		$this->assertTrue( registered_meta_key_exists( 'post', 'test_meta', 'post' ) );
		$this->assertFalse( registered_meta_key_exists( 'post', 'test_meta', 'page' ) );
	}
}

class Testable_Post_Model_Meta extends Post {
	use Register_Meta;

	public static $object_name = 'post';

	protected static function boot() {
		static::register_meta( 'test_meta' );
	}
}
