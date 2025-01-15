<?php
namespace Mantle\Tests\Database\Factory;

use Carbon\Carbon;
use Closure;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\retry;

/**
 * Test case with the focus of testing the unit testing factory that mirrors
 * WordPress core's factories. The factories here should be drop-in replacements
 * for core's factories with some sugar on top.
 *
 * @group factory
 */
#[Group( 'factory' )]
class UnitTestingFactoryTest extends Framework_Test_Case {
	use With_Faker;

	public function test_post_factory() {
		$this->assertInstanceOf( \WP_Post::class, static::factory()->post->create_and_get() );

		$post_ids = static::factory()->post->create_many(
			10,
			[
				'post_type'   => 'post',
				'post_status' => 'draft',
			]
		);

		$this->assertCount( 10, $post_ids );
		foreach ( $post_ids as $post_id ) {
			$this->assertIsInt( $post_id );
		}

		$this->assertEquals( 'draft', get_post_status( array_shift( $post_ids ) ) );
	}

	public function test_post_create_with_thumbnail() {
		$post_id = static::factory()->post->create_with_thumbnail();

		$this->assertTrue( has_post_thumbnail( $post_id ) );
	}

	public function test_post_with_thumbnail_middleware() {
		$post_id = static::factory()->post->with_thumbnail()->create();

		$this->assertTrue( has_post_thumbnail( $post_id ) );
	}

	public function test_create_ordered_set() {
		retry( 3, function () {
			$post_ids = static::factory()->post->create_ordered_set( 10, [
				'meta' => [
					'_test_date_meta_key' => '_test_meta_value',
				],
			] );

			$this->assertCount( 10, $post_ids );

			$dates = collect( $post_ids )
				->map( fn ( $post_id ) => Carbon::parse( get_post( $post_id )->post_date ) )
				->to_array();

			foreach ( $dates as $i => $date ) {
				if ( isset( $dates[ $i - 1 ] ) ) {
					$this->assertEquals(
						3600,
						$date->diffInSeconds( $dates[ $i - 1 ] ),
						'Distance between posts not expected 3600 seconds',
					);
				}
			}

			// Query the posts and ensure the order matches.
			$queried_post_ids = get_posts( [
				'fields'           => 'ids',
				'meta_key'         => '_test_date_meta_key',
				'meta_value'       => '_test_meta_value',
				'order'            => 'DESC',
				'orderby'          => 'post_date',
				'posts_per_page'   => 50,
				'suppress_filters' => false,
			] );

			$this->assertCount( 10, $queried_post_ids );

			// Posts should be in the opposite order since we're sorting by descending date.
			$this->assertEquals( array_reverse( $post_ids ), $queried_post_ids );
		} );
	}

	public function test_attachment_factory() {
		$this->shim_test( \WP_Post::class, 'attachment' );

		$attachment = static::factory()->attachment->create_and_get();

		$this->assertEquals( 'attachment', get_post_type( $attachment ) );
	}

	public function test_term_factory() {
		$this->shim_test( \WP_Term::class, 'category' );
		$this->shim_test( \WP_Term::class, 'tag' );
	}

	public function test_blog_factory() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		$this->shim_test( \WP_Site::class, 'blog' );
	}

	public function test_network_factory() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		$this->shim_test( \WP_Network::class, 'network' );
	}

	public function test_user_factory() {
		$this->shim_test( \WP_User::class, 'user' );
	}

	public function test_user_meta_factory() {
		$user_id = static::factory()->user->with_meta(
			[
				'_test_meta_key' => '_test_meta_value',
			]
		)->create();

		$this->assertEquals( '_test_meta_value', get_user_meta( $user_id, '_test_meta_key', true ) );
	}

	public function test_user_login_factory() {
		$user_login = $this->faker->userName;
		$user       = static::factory()->user
			->create_and_get( [ 'user_login' => $user_login ] );

		$this->assertSame( $user_login, $user->display_name );
		$this->assertSame( $user_login, $user->user_login );
	}

	public function test_comment_factory() {
		$this->shim_test( \WP_Comment::class, 'comment' );
	}

	public function test_as_models() {
		$post = static::factory()->post->as_models()->create_and_get();
		$term = static::factory()->term->with_model( Testable_Post_Tag::class )->as_models()->create_and_get();

		$this->assertInstanceOf( Post::class, $post );
		$this->assertInstanceOf( Testable_Post_Tag::class, $term );
	}

	public function test_factory_middleware() {
		$middleware = function ( $item, Closure $next ) {
			$post = $next( $item );

			update_post_meta( $post->ID, '_test_meta_key', '_test_meta_value' );

			return $post;
		};

		$post = static::factory()->post->with_middleware( $middleware )->create_and_get();

		$this->assertEquals( '_test_meta_value', get_post_meta( $post->ID, '_test_meta_key', true ) );
	}

	#[Group( 'with_terms' )]
	public function test_posts_with_terms() {
		$post = static::factory()->post->with_terms(
			[
				$category = static::factory()->category->create_and_get(),
			],
			static::factory()->category->create_and_get(),
		)->create_and_get();

		$this->assertTrue( has_term( $category->term_id, 'category', $post ) );
	}

	#[Group( 'with_terms' )]
	public function test_posts_with_term_ids() {
		$post = static::factory()->post->with_terms(
			[
				$category_id = static::factory()->category->create(),
				$category    = static::factory()->category->create_and_get(),
			],
			static::factory()->category->create_and_get(),
		)->create_and_get();

		$this->assertTrue( has_term( $category_id, 'category', $post ) );
		$this->assertTrue( has_term( $category->term_id, 'category', $post ) );
	}

	#[Group( 'with_terms' )]
	public function test_posts_with_terms_multiple_taxonomies() {
		$post = static::factory()->post->with_terms(
			$category = static::factory()->category->create_and_get(),
			$tag = static::factory()->tag->create_and_get(),
		)->create_and_get();

		$this->assertTrue( has_term( $category->term_id, 'category', $post ) );
		$this->assertTrue( has_term( $tag->term_id, 'post_tag', $post ) );

		$post = static::factory()->post->with_terms( [
			$category = static::factory()->category->create_and_get(),
			$tag = static::factory()->tag->create_and_get(),
		] )->create_and_get();

		$this->assertTrue( has_term( $category->term_id, 'category', $post ) );
		$this->assertTrue( has_term( $tag->term_id, 'post_tag', $post ) );
	}

	#[Group( 'with_terms' )]
	public function test_posts_with_multiple_taxonomies_with_mixed_objects() {
		$tag = static::factory()->tag->create_and_get();

		// Test with the arguments passed as individual parameters.
		$post = static::factory()->post->with_terms(
			$category = static::factory()->category->create_and_get(),
			[
				'post_tag' => $tag->slug,
			],
		)->create_and_get();

		$this->assertTrue( has_term( $category->term_id, 'category', $post ) );
		$this->assertTrue( has_term( $tag->term_id, 'post_tag', $post ) );

		// Test with the arguments wrapped in an array.
		$post = static::factory()->post->with_terms( [
			$category = static::factory()->category->create_and_get(),
			[
				'post_tag' => $tag->slug,
			],
		] )->create_and_get();

		$this->assertTrue( has_term( $category->term_id, 'category', $post ) );
		$this->assertTrue( has_term( $tag->term_id, 'post_tag', $post ) );
	}

	#[Group( 'with_terms' )]
	public function test_posts_with_multiple_terms_single_array_argument() {
		$tags = collect( static::factory()->tag->create_many( 5 ) )
			->map( fn ( $term_id ) => get_term( $term_id ) )
			->pluck( 'term_id' )
			->all();

		$post = static::factory()->post->with_terms( $tags )->create_and_get();

		$post_tags = get_the_terms( $post, 'post_tag' );

		$this->assertCount( 5, $post_tags );
		$this->assertEquals(
			collect( $tags )->sort()->values()->all(),
			collect( $post_tags )->pluck( 'term_id' )->sort()->values()->all(),
		);
	}

	#[Group( 'with_terms' )]
	public function test_posts_with_multiple_terms_spread_array_argument() {
		$tags = collect( static::factory()->tag->create_many( 5 ) )
			->map( fn ( $term_id ) => get_term( $term_id ) )
			->pluck( 'term_id' )
			->all();

		$post = static::factory()->post->with_terms( ...$tags )->create_and_get();

		$post_tags = get_the_terms( $post, 'post_tag' );

		$this->assertCount( 5, $post_tags );
		$this->assertEquals(
			collect( $tags )->sort()->values()->all(),
			collect( $post_tags )->pluck( 'term_id' )->sort()->values()->all(),
		);
	}

	/**
	 * @dataProvider slug_id_dataprovider
	 */
	#[DataProvider( 'slug_id_dataprovider' )]
	#[Group( 'with_terms' )]
	public function test_posts_with_multiple_terms_single_array( string $field ) {
		$tags = collect( static::factory()->tag->create_many( 5 ) )
			->map( fn ( $term_id ) => get_term( $term_id ) )
			->pluck( $field )
			->all();

		$post = static::factory()->post->with_terms( [ 'post_tag' => $tags ] )->create_and_get();
		$post_tags = get_the_terms( $post, 'post_tag' );

		$this->assertCount( 5, $post_tags );
		$this->assertEquals(
			collect( $tags )->sort()->values()->all(),
			collect( $post_tags )->pluck( $field )->sort()->values()->all(),
		);
	}

	#[Group( 'with_terms' )]
	public function test_posts_with_terms_create_unknown_term() {
		$post = static::factory()->post->with_terms( [
			'post_tag' => [ 'unknown-term' ],
		] )->create_and_get();

		$post_tags = get_the_terms( $post, 'post_tag' );

		$this->assertCount( 1, $post_tags );
		$this->assertEquals( 'unknown-term', $post_tags[0]->slug );
	}

	public function test_terms_with_posts() {
		$post_ids = static::factory()->post->create_many( 2 );

		$category = static::factory()->category->with_posts( $post_ids )->create_and_get();

		$this->assertTrue( has_term( $category->term_id, 'category', $post_ids[0] ) );
		$this->assertTrue( has_term( $category->term_id, 'category', $post_ids[1] ) );
	}

	public function test_post_with_meta() {
		$post = static::factory()->post->with_meta(
			[
				'_test_meta_key' => '_test_meta_value',
			],
		)->with_meta( '_test_string', 'the value' )->create_and_get();

		$this->assertEquals( '_test_meta_value', get_post_meta( $post->ID, '_test_meta_key', true ) );
		$this->assertEquals( 'the value', get_post_meta( $post->ID, '_test_string', true ) );

		$post_ids = static::factory()->post->with_meta(
			[
				'_test_meta_key' => '_test_meta_value',
			],
		)->create_many( 10 );

		foreach ( $post_ids as $post_id ) {
			$this->assertEquals( '_test_meta_value', get_post_meta( $post_id, '_test_meta_key', true ) );
		}
	}

	public function test_attachment_with_image() {
		$attachment = static::factory()->attachment->with_image()->create();

		$this->assertNotEmpty( wp_get_attachment_image_url( $attachment ) );
	}

	public function test_post_with_real_image() {
		$post = static::factory()->post->with_real_thumbnail()->create();

		$this->assertNotEmpty( wp_get_attachment_image_url( get_post_thumbnail_id( $post ) ) );
	}

	protected function shim_test( string $class_name, string $property ) {
		$this->assertInstanceOf( $class_name, static::factory()->$property->create_and_get() );

		$object_ids = static::factory()->$property->create_many( 10 );
		foreach ( $object_ids as $object_id ) {
			$this->assertIsInt( $object_id );
		}

		$this->assertCount( 10, $object_ids );
	}

	/**
	 * @dataProvider dataprovider_factory
	 */
	#[DataProvider( 'dataprovider_factory' )]
	public function test_dataprovider_factory( callable $fn ) {
		$post = $fn();

		$this->assertInstanceOf( \WP_Post::class, $post );
		$this->assertStringContainsString(
			'<!-- wp:paragraph',
			$post->post_content,
		);
	}

	public static function dataprovider_factory(): array {
		return [
			'example' => [ fn () => static::factory()->post->create_and_get() ],
		];
	}

	public function test_custom_post_type() {
		register_post_type( 'custom-post' );

		$post = static::factory()->post->for( 'custom-post' )->create_and_get();

		$this->assertEquals( 'custom-post', get_post_type( $post ) );
	}

	public function test_custom_taxonomy() {
		register_taxonomy( 'custom-taxonomy', 'post' );

		$term = static::factory()->term->for( 'custom-taxonomy' )->create_and_get();

		$this->assertEquals( 'custom-taxonomy', get_taxonomy( $term->taxonomy )->name );
	}

	public function test_dynamic_factory() {
		register_post_type( 'events' );
		register_taxonomy( 'event-category', 'events' );

		$event = static::factory()->events->create_and_get();

		$this->assertInstanceOf( \WP_Post::class, $event );
		$this->assertEquals( 'events', get_post_type( $event ) );

		$term = static::factory()->{'event-category'}->create_and_get();

		$this->assertInstanceOf( \WP_Term::class, $term );
		$this->assertEquals( 'event-category', get_taxonomy( $term->taxonomy )->name );
	}

	public function test_dynamic_factory_unknown() {
		$this->expectException( \InvalidArgumentException::class );

		static::factory()->unknown->create_and_get();
	}

	public function test_dynamic_factory_conflict() {
		register_post_type( 'conflict' );
		register_taxonomy( 'conflict', 'conflict' );

		$this->expectException( \InvalidArgumentException::class );

		static::factory()->conflict->create_and_get();
	}

	public static function slug_id_dataprovider(): array {
		return [
			'term_id' => [ 'term_id' ],
			'slug'    => [ 'slug' ],
		];
	}
}

class Testable_Post_Tag extends Term {
	public static $object_name = 'post_tag';
}
