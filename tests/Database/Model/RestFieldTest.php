<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Registration\Register_Rest_Fields;
use Mantle\Testing\Framework_Test_Case;

class RestFieldTest extends Framework_Test_Case {
	public function test_rest_field() {
		Testable_Model_With_Rest_Field::boot_if_not_booted();

		$post_id = static::factory()->post->create();

		$this->get( rest_url( "/wp/v2/posts/{$post_id}" ) )
			->assertJsonPath( 'id', $post_id )
			->assertJsonPath( 'field-to-register', 'value to return' );
	}
}

class Testable_Model_With_Rest_Field extends Post {
	use Register_Rest_Fields;

	public static $object_name = 'post';

	protected static function boot() {
		static::register_field(
			'field-to-register',
			function() {
				return 'value to return';
			}
		);
	}
}
