<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Concerns\Unregister_All_Meta_Keys;
use Mantle\Testing\FrameworkTestCase;

/**
 * Test for the \Mantle\Testing\Concerns\Unregister_All_Meta_Keys trait.
 */
class UnregisterAllMetaKeysTest extends FrameworkTestCase {
	use Unregister_All_Meta_Keys;

	public function test_register_meta_in_first_test_run(): void {
		register_meta( 'post', 'test_meta_key_before_tear_down', [
			'single'       => true,
			'show_in_rest' => true,
			'type'         => 'string',
		] );

		$this->assertTrue(  registered_meta_key_exists( 'post', 'test_meta_key_before_tear_down' )  );
	}

	public function test_ensure_meta_registered_before_does_not_exist_anymore(): void {
		$this->assertFalse(  registered_meta_key_exists( 'post', 'test_meta_key_during_test' )  );
	}
}
