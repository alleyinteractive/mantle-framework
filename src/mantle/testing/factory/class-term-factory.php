<?php
/**
 * Term_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Faker\Generator;
use Mantle\Database\Model\Term;

use function Mantle\Support\Helpers\get_term_object;

/**
 * Term Factory
 *
 * @template TObject
 */
class Term_Factory extends Factory {
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
