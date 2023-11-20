<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Permalink_Generator;
use Mantle\Database\Model\Post;
use Mantle\Testing\Framework_Test_Case;

class PermalinkGeneratorTest extends Framework_Test_Case {
	public function test_generate_permalink() {
		$post  = static::factory()->post->create_and_get();
		$model = Testable_Post_Generator::find( $post->ID );

		$this->assertEquals(
			home_url( '/base/' . $post->post_name ),
			(string) Permalink_Generator::create( '/base/{slug}', $model ),
		);
	}

	public function test_generate_permalink_alias() {
		$post  = static::factory()->post->create_and_get();
		$model = Testable_Post_Alias::find( $post->ID );

		$this->assertEquals(
			home_url( '/url-base/' . $post->post_name ),
			(string) Permalink_Generator::create( '/url-base/{slug_alias}', $model ),
		);
	}
}

class Testable_Post_Generator extends Post {
	public static $object_name = 'post';
}

class Testable_Post_Alias extends Post {
	public static $object_name = 'post';

	protected static $aliases = [
		'slug_alias' => 'post_name',
	];
}
