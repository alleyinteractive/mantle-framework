<?php
/**
 * Term_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Factory;

use Faker\Generator;
use Mantle\Framework\Database\Model\Term;

/**
 * Term Factory
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
	 * @return mixed The result. Can be anything.
	 */
	public function create( $args ) {
		$args = array_merge(
			[
				'description' => trim( $this->faker->paragraph( 2 ) ),
				'name'        => $this->faker->sentence(),
				'taxonomy'    => $this->taxonomy,
			],
			$args
		);

		return Term::create( $args )->core_object();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return mixed The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return \get_term( $object_id );
	}
}
