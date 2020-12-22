<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Database\Query\Post_Query_Builder as Builder;
use Mantle\Framework\Database\Query\Post_Query_Builder;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Framework_Test_Case;


class Test_Eager_Load_Relationships extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();
		register_post_type( Another_Testable_Post_Eager::get_object_name() );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_post_type( Another_Testable_Post_Eager::get_object_name() );
	}

	public function test_relationship_as_attribute() {
		$post = Testable_Post_Eager::find( static::factory()->post->create() );
		$another_post = $post->post_relationship()->save( new Another_Testable_Post_Eager(
			[
				'post_status' => 'publish',
				'post_title' => 'Another Testable Post',
			]
		) );

		// Add some posts after to make this random.
		static::factory()->post->create_many(10);
		static::factory()->post->create_many(10, ['post_type' => 'example-post-eager']);

		$this->assertEquals( $another_post->id, $post->post_relationship->id );
		$this->assertEquals( $post->id, $another_post->post->id );
	}
}

class Testable_Post_Eager extends Post {
	public static $object_name = 'post';

	public function post_relationship() {
		return $this->has_one( Another_Testable_Post_Eager::class );
	}

	public function term_relationship() {
		return $this->has_many( Testable_Tag_Eager::class );
	}
}

class Another_Testable_Post_Eager extends Post {
	public static $object_name = 'example-post-eager';

	public function post() {
		return $this->belongs_to( Testable_Post_Eager::class );
	}
}

class Testable_Tag_Eager extends Term {
	public static $object_name = 'post_tag';
}
