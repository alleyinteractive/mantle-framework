<?php
namespace Mantle\Tests\Database\Builder;

use Carbon\Carbon;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Database\Query\Post_Query_Builder as Builder;
use Mantle\Framework\Database\Query\Post_Query_Builder;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Framework_Test_Case;

use function Mantle\Framework\Helpers\collect;

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
		$this->assertNull( $post->post_relationship );

		$another_post = $post->post_relationship()->save( new Another_Testable_Post_Eager(
			[
				'post_status' => 'publish',
				'post_title' => 'Another Testable Post',
			]
		) );

		// Add some posts after to make this random.
		static::factory()->post->create_many( 10 );
		static::factory()->post->create_many( 10, [ 'post_type' => 'example-post-eager' ] );

		$this->assertEquals( $another_post->id, $post->post_relationship->id );
		$this->assertEquals( $post->id, $another_post->post->id );
	}

	public function test_eager_loading_relationships_has_one() {
		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				function() use ( &$related_post_ids ) {
					$post = Testable_Post_Eager::find( static::factory()->post->create() );

					$related_post = $post->post_relationship()->save( new Another_Testable_Post_Eager(
						[
							'post_status' => 'publish',
							'post_title' => "$post->title relation"
						]
					) );

					// Store the ID for testing later.
					$related_post_ids[ $post->id ] = $related_post->id;

					return $post;
				}
			)
			->to_array();

		// Eager load the models.
		$posts = Testable_Post_Eager::with( 'post_relationship' )->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'post_relationship' ) );
			if (!is_object($post)) {
				dump($post);
			}
			$this->assertEquals( $related_post_ids[ $post->id ] ?? null, $post->post_relationship->id );
		}
	}

	public function test_eager_loading_relationships_has_many() {
		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				function() use ( &$related_post_ids ) {
					$post = Testable_Post_Eager::find( static::factory()->post->create() );

					for ( $i = 0; $i < 3; $i++ ) {
						$related_post = $post->posts_relationship()->save( new Another_Testable_Post_Eager(
							[
								'post_status' => 'publish',
								'post_title' => "$post->title relation"
							]
						) );

						// Store the ID for testing later.
						$related_post_ids[ $post->id ][] = $related_post->id;
					}

					return $post;
				}
			)
			->to_array();

		// Eager load the models.
		$posts = Testable_Post_Eager::with('posts_relationship')->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'posts_relationship' ) );
			$this->assertNotEmpty( $related_post_ids[ $post->id ] ?? [] );

			$ids = collect( $post->posts_relationship )->pluck( 'id' )->all();

			foreach ( $related_post_ids[ $post->id ] as $related_id ) {
				$this->assertTrue( in_array( $related_id, $ids, true ) );
			}
		}
	}

	public function test_eager_loading_relationships_belongs_to() {
		$related_post_ids = [];
		$posts = collect()
			->pad(10, null)
			->map(
				function() use ( &$related_post_ids ) {
					// $related_post = Another_Testable_Post_Eager::
					$related_post = Another_Testable_Post_Eager::find( static::factory()->post->create( [ 'post_type' => 'example-post-eager' ] ) );

					$post = $related_post->post()->save( new Testable_Post_Eager(
						[
							'post_status' => 'publish',
							'post_title' => "$related_post->title relation"
						]
					) );

					// Store the ID for testing later.
					$related_post_ids[ $related_post->id ] = $post->id;

					return $post;
				}
			)
			->to_array();

		// Eager load the models.
		$posts = Another_Testable_Post_Eager::with('post')->get();

		foreach ( $posts as $post ) {
			$this->assertTrue( $post->relation_loaded( 'post' ) );
			$this->assertEquals( $related_post_ids[ $post->id ], $post->post->id );
		}
	}

	// public function test_eager_loading_term_has_one() {
	// 	$tag = Testable_Tag_Eager::create( [ 'name' => 'Test Eager Loading Term' ] );
	// 	$post = Testable_Post_Eager::create(
	// 		[
	// 			'title'     => 'Testable Post Eager for Testable Term',
	// 			'post_date' => Carbon::now()->subWeek()->toDateTimeString(),
	// 		]
	// 	);

	// 	static::factory()->post->create_many( 10 );
	// 	static::factory()->tag->create_many( 10 );
	// }
}

class Testable_Post_Eager extends Post {
	public static $object_name = 'post';

	public function post_relationship() {
		return $this->has_one( Another_Testable_Post_Eager::class );
	}

	public function posts_relationship() {
		return $this->has_many( Another_Testable_Post_Eager::class );
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
