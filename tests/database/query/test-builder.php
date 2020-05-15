<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Framework\Database\Model\Comment;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Query\Builder;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Comment_Object extends WP_UnitTestCase {
	public function test_post_by_name() {
		$post = static::factory()->post->create( [ 'post_name' => 'post-to-find' ] );

		$first = Builder::create( Testable_Post::class )
			->whereSlug( 'post-to-find' )
			->first();

		$this->assertInstanceOf( Testable_Post::class, $first );
		$this->assertEquals( 'post-to-find', $first->slug() );
		$this->assertEquals( $post, $first->id() );
	}

	public function test_post_by_name_not_found() {
		static::factory()->post->create( [ 'post_name' => 'post-to-not-find' ] );

		$first = Builder::create( Testable_Post::class )
			->whereSlug( 'post-name-we-are-looking-for' )
			->first();

		$this->assertNull( $first );
	}

	public function test_post_by_name_override() {
		static::factory()->post->create( [ 'post_name' => 'post-to-find' ] );

		$first = Builder::create( Testable_Post::class )
			->whereSlug( 'post-name-we-are-looking-for' )
			->whereSlug( 'post-to-find' )
			->first();

		$this->assertEquals( 'post-to-find', $first->slug() );
	}

	// public function test_post_by_post_name() {
	// 	$post_name = 'example-post-name-to-search';
	// 	$post = static::factory()->post->create( [ 'post_name' => $post_name ] );

	// 	$first = Builder::create( Testable_Post::class )
	// 		->wherePostName( $post_name )
	// 		->first();

	// 	$this->assertInstanceOf( Testable_Post::class, $first );
	// 	$this->assertEquals( $post_name, $first->slug() );
	// 	$this->assertEquals( $post->ID, $first->id() );
	// }
}

class Testable_Post extends Post {
	public static $object_name = 'post';
}
