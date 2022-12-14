<?php
/**
 * Post_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Carbon\Carbon;
use Closure;
use Faker\Generator;
use Mantle\Database\Model\Post;
use WP_Post;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\get_post_object;

/**
 * Post Factory
 *
 * @template TObject
 */
class Post_Factory extends Factory {
	use Concerns\With_Meta;

	/**
	 * Faker instance.
	 *
	 * @var Generator
	 */
	protected $faker;

	/**
	 * Post type to use.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Constructor.
	 *
	 * @param Generator $generator Faker generator.
	 * @param string    $post_type Post type to use.
	 */
	public function __construct( Generator $generator, string $post_type = 'post' ) {
		$this->faker     = $generator;
		$this->post_type = $post_type;
	}

	/**
	 * Create a new factory instance to create posts with a set of terms.
	 *
	 * @param array<int, WP_Term|int|string>|WP_Term|int|string> ...$terms Terms to assign to the post.
	 * @return static
	 */
	public function with_terms( ...$terms ) {
		$terms = collect( $terms )->flatten()->all();

		return $this->with_middleware(
			fn ( array $args, Closure $next ) => $next( $args )->set_terms( $terms ),
		);
	}

	/**
	 * Create a new factory instance to create posts with a thumbnail.
	 *
	 * @return static
	 */
	public function with_thumbnail() {
		return $this->with_meta(
			[
				'_thumbnail_id' => ( new Attachment_Factory( $this->faker ) )->create(),
			]
		);
	}

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	public function create( array $args = [] ): ?int {
		$args = array_merge(
			[
				'content'   => trim( $this->faker->randomHtml() ),
				'excerpt'   => trim( $this->faker->paragraph( 2 ) ),
				'status'    => 'publish',
				'title'     => $this->faker->sentence(),
				'post_type' => $this->post_type,
			],
			$args
		);

		return $this->make( $args, Post::class )?->id();
	}

	/**
	 * Create a post with a thumbnail.
	 *
	 * @deprecated Use {@see Post_Factory::with_thumbnail()} instead.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	public function create_with_thumbnail( array $args = [] ): ?int {
		return $this->with_thumbnail()->create( $args );
	}

	/**
	 * Create an ordered set of posts.
	 *
	 * Useful to create posts in a specific order for testing. Creates posts in
	 * chronological order separated by a defined number of seconds, the
	 * default of which is equal to 1 hour.
	 *
	 * @param int           $count The number of posts to create.
	 * @param array         $args The arguments.
	 * @param Carbon|string $starting_date The starting date for the posts, defaults to
	 *                                     a month ago.
	 * @param int           $separation The number of seconds between each post.
	 * @return array<int, int>
	 */
	public function create_ordered_set(
		int $count = 10,
		array $args = [],
		$starting_date = null,
		int $separation = 3600
	): array {
		if ( ! ( $starting_date instanceof Carbon ) ) {
			$starting_date = $starting_date
				? Carbon::parse( $starting_date )
				: Carbon::now()->subMonth();
		}

		// Set the date for the first post (seconds added before each run).
		$date = $starting_date->subSeconds( $separation );

		return collect()
			->pad( $count, null )
			->map(
				fn() => $this->create(
					array_merge(
						$args,
						[
							'date' => $date->addSeconds( $separation )->format( 'Y-m-d H:i:s' ),
						]
					)
				)
			)
			->to_array();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return Post|WP_Post|null
	 */
	public function get_object_by_id( int $object_id ) {
		return $this->as_models
			? Post::find( $object_id )
			: get_post_object( $object_id );
	}
}
