<?php
/**
 * Post_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Faker\Generator;
use Mantle\Database\Model\Attachment;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Support\Arr;
use WP_Post;
use WP_Term;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\get_post_object;
use function Mantle\Support\Helpers\get_term_object;
use function Mantle\Support\Helpers\tap;

/**
 * Post Factory
 *
 * @template TModel of \Mantle\Database\Model\Post
 * @template TObject
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Post_Factory extends Factory {
	use Concerns\With_Byline_Manager_Profiles;
	use Concerns\With_Guest_Authors;
	use Concerns\With_Meta;

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = Post::class;

	/**
	 * Flag to create terms by default.
	 */
	protected bool $create_terms = true;

	/**
	 * Flag to append terms by default.
	 */
	protected bool $append_terms = true;

	/**
	 * Constructor.
	 *
	 * @param Generator $faker Faker generator.
	 * @param string    $post_type Post type to use.
	 */
	public function __construct( Generator $faker, public string $post_type = 'post' ) {
		parent::__construct( $faker );
	}

	/**
	 * Change the default creation of terms with the post factory.
	 *
	 * @param bool $value Value to set.
	 */
	public function create_terms( bool $value = true ): void {
		$this->create_terms = $value;
	}

	/**
	 * Change the default appending of terms with the post factory.
	 *
	 * @param bool $value Value to set.
	 */
	public function append_terms( bool $value = true ): void {
		$this->append_terms = $value;
	}

	/**
	 * Create a new factory instance to create posts with a set of terms.
	 *
	 * Any slugs passed that are not found will be created. If you want to
	 * only use existing terms, use `with_terms_only_existing()`.
	 *
	 * @param array<int|string, \WP_Term|int|string|array<string, mixed>>|\WP_Term|int|string ...$terms Terms to assign to the post.
	 */
	public function with_terms( ...$terms ): static {
		$terms = collect( Arr::wrap( $terms ) )->all();

		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $terms ): Post {
				$category_ids = [];

				// Attempt to find all the categories passed to the method.
				foreach ( $terms as $term_argument ) {
					if (
						( $term_argument instanceof WP_Term || $term_argument instanceof Term )
						&& 'category' === $term_argument->taxonomy
					) {
						$category_ids[] = $term_argument->term_id;

						continue;
					}

					if ( is_numeric( $term_argument ) ) {
						$term = get_term_object( $term_argument );

						if ( $term instanceof \WP_Term && 'category' === $term->taxonomy ) {
							$category_ids[] = $term->term_id;
						}

						continue;
					}

					if ( is_string( $term_argument ) ) {
						$term = get_term_object( $term_argument, 'category' );

						if ( $term instanceof \WP_Term ) {
							$category_ids[] = $term->term_id;
						}

						continue;
					}
				}

				// Pass categories to the creation of the post model if passed. This
				// will prevent a post from being created with a default category and an
				// additional one added on top.
				if ( ! empty( $category_ids ) ) {
					if ( ! isset( $args['post_category'] ) || ! is_array( $args['post_category'] ) ) {
						$args['post_category'] = [];
					}

					$args['post_category'] = array_merge( $args['post_category'], $category_ids );
				}

				return $next( $args )->set_terms(
					terms: $terms,
					append: $this->append_terms,
					create: $this->create_terms,
				);
			},
		);
	}

	/**
	 * Create a new factory instance to create posts with a set of terms without creating
	 * any unknown terms.
	 *
	 * @param array<int|string, \WP_Term|int|string|array<string, mixed>>|\WP_Term|int|string ...$terms Terms to assign to the post.
	 */
	public function with_terms_only_existing( ...$terms ): static {
		// Handle an array in the first argument.
		if ( 1 === count( $terms ) && isset( $terms[0] ) && is_array( $terms[0] ) ) {
			$terms = $terms[0];
		}

		$terms = collect( $terms )->all();

		return $this->with_middleware(
			fn ( array $args, Closure $next ) => $next( $args )->set_terms( $terms, append: $this->append_terms, create: false ),
		);
	}

	/**
	 * Attach a post thumbnail to the post.
	 *
	 * Note: the underlying attachment does not actually exist for performance.
	 * You can use `with_real_thumbnail()` to create a real underlying attachment
	 * for the post thumbnail.
	 */
	public function with_thumbnail(): static {
		return $this->with_meta(
			[
				'_thumbnail_id' => ( new Attachment_Factory( $this->faker ) )->create(),
			]
		);
	}

	/**
	 * Attach a thumbnail to the post with an underlying file attachment.
	 *
	 * @param callable|string|null $file   The file name to create attachment object from.
	 * @phpstan-param (callable(): string)|string|null $file
	 * @param int                  $width  The width of the image.
	 * @param int                  $height The height of the image.
	 * @param bool                 $recycle Whether to recycle the image file.
	 */
	public function with_real_thumbnail( callable|string|null $file = null, int $width = 1200, int $height = 800, bool $recycle = true ): static {
		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $file, $width, $height, $recycle ) {
				$post = $next( $args );

				update_post_meta(
					$post->ID,
					'_thumbnail_id',
					Attachment::factory()->with_image(
						file: $file,
						parent: $post->ID,
						width: $width,
						height: $height,
						recycle: $recycle
					)->create(),
				);

				return $post;
			}
		);
	}

	/**
	 * Create a new factory instance to create posts for a specific post type.
	 *
	 * @param string $post_type Post type to use.
	 */
	public function with_post_type( string $post_type ): static {
		return tap(
			clone $this,
			fn ( Post_Factory $factory ) => $factory->post_type = $post_type,
		);
	}

	/**
	 * Alias for {@see Post_Factory::with_post_type()}.
	 *
	 * @param string $post_type Post type to use.
	 */
	public function for( string $post_type ): static {
		return $this->with_post_type( $post_type );
	}

	/**
	 * Create a new factory instance to create scheduled posts.
	 *
	 * @throws \InvalidArgumentException If the date is not in the future.
	 *
	 * @param DateTimeInterface|string|null $date The date to schedule the post for. Defaults to 1 day in the future.
	 */
	public function scheduled( DateTimeInterface|string|null $date = null ): static {
		$date = match ( true ) {
			$date instanceof DateTimeInterface => $date->format( 'Y-m-d H:i:s' ),
			is_string( $date )                 => $date,
			default                            => Carbon::now()->addDay()->format( 'Y-m-d H:i:s' ),
		};

		// Ensure the date is in the future.
		if ( strtotime( $date ) <= current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			throw new \InvalidArgumentException( 'The date for a scheduled post must be in the future.' );
		}

		return $this->state( [
			'post_date'     => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
			'post_status'   => 'future',
		] );
	}

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return [
			'post_content' => trim( (string) $this->faker->paragraph_blocks( 3 ) ),
			'post_excerpt' => trim( $this->faker->paragraph( 2 ) ),
			'post_status'  => 'publish',
			'post_title'   => $this->faker->sentence(),
			'post_type'    => $this->post_type,
		];
	}

	/**
	 * Create a post with a thumbnail.
	 *
	 * @deprecated Use {@see Post_Factory::with_thumbnail()} instead.
	 *
	 * @param array $args The arguments.
	 */
	#[\Deprecated( 'Use the `with_thumbnail()` method instead.' )]
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
	 * @param array<mixed>  $args The arguments.
	 * @param Carbon|string $starting_date The starting date for the posts, defaults to
	 *                                     a month ago.
	 * @param int           $separation The number of seconds between each post.
	 * @return array<int, int>
	 */
	public function create_ordered_set(
		int $count = 10,
		array $args = [],
		Carbon|string|null $starting_date = null,
		int $separation = 3600
	): array {
		if ( ! ( $starting_date instanceof Carbon ) ) {
			$starting_date = $starting_date
				? Carbon::parse( $starting_date )
				: Carbon::now()->subSeconds( $separation * $count )->startOfMinute();
		}

		// Set the date for the first post (seconds added before each run).
		$starting_date->subSeconds( $separation );

		return collect()->times( $count, fn () => $this->create( array_merge( $args, [
			'date' => $starting_date->addSeconds( $separation )->format( 'Y-m-d H:i:s' ),
		] ) ) )->all();
	}

	/**
	 * Create and get an ordered set of posts.
	 *
	 * @see Post_Factory::create_ordered_set() for details.
	 *
	 * @param int           $count The number of posts to create.
	 * @param array<mixed>  $args The arguments.
	 * @param Carbon|string $starting_date The starting date for the posts, defaults to
	 *                                     a month ago.
	 * @param int           $separation The number of seconds between each post.
	 * @return array<int, TModel>
	 * @phpstan-return array<int, TModel>
	 */
	public function create_ordered_set_and_get(
		int $count = 10,
		array $args = [],
		Carbon|string|null $starting_date = null,
		int $separation = 3600
	): array {
		return collect( $this->create_ordered_set( $count, $args, $starting_date, $separation ) )
			->map( $this->get_object_by_id( ... ) )
			->all();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return Post|WP_Post|null
	 * @phpstan-return TModel|TObject|null
	 */
	public function get_object_by_id( int $object_id ) {
		return $this->as_models
			? $this->model::find( $object_id )
			: get_post_object( $object_id );
	}
}
