<?php
/**
 * Term_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Closure;
use Faker\Generator;
use Mantle\Database\Model\Term;

use function Mantle\Support\Helpers\get_term_object;

/**
 * Term Factory
 *
 * @template TObject
 */
class Term_Factory extends Factory {
	use Concerns\With_Meta;

	/**
	 * Faker instance.
	 *
	 * @var Generator
	 */
	protected $faker;

	/**
	 * Taxonomy name.
	 *
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * Constructor.
	 *
	 * @param Generator $generator Faker generator.
	 * @param string    $taxonomy Taxonomy name.
	 */
	public function __construct( Generator $generator, string $taxonomy ) {
		$this->faker    = $generator;
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	public function create( array $args = [] ) {
		$args = array_merge(
			[
				'description' => trim( $this->faker->paragraph( 2 ) ),
				'name'        => $this->faker->sentence(),
				'taxonomy'    => $this->taxonomy,
			],
			$args
		);

		return $this->make( $args, Term::class )?->id();
	}

	/**
	 * Create a new factory instance to create terms with a set of posts.
	 *
	 * Supports an array of post IDs or WP_Post objects.
	 *
	 * @param array<int, \WP_Post|int>|\WP_Post|int> ...$posts Posts to assign to the term.
	 * @return static
	 */
	public function with_posts( ...$posts ) {
		$posts = collect( $posts )->flatten()->all();

		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $posts ) {
				/**
				 * The created term.
				 *
				 * @var \WP_Term $term
				 */
				$term = $next( $args );

				foreach ( $posts as $post ) {
					if ( $post instanceof \WP_Post ) {
						wp_set_object_terms( $post->ID, $term->term_id, $term->taxonomy, true );
					} else {
						wp_set_object_terms( $post, $term->term_id, $term->taxonomy, true );
					}
				}

				return $term;
			},
		);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_Term|null
	 */
	public function get_object_by_id( int $object_id ) {
		$term = get_term_object( $object_id );

		if ( $term && $this->as_models ) {
			return Term::new_from_existing( (array) $term );
		}

		return $term;
	}
}
