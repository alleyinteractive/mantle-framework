<?php
/**
 * Blog_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Factory;

use Faker\Generator;
use Mantle\Framework\Database\Model\Site;

/**
 * Blog Factory
 */
class Blog_Factory extends Factory {
	/**
	 * Faker instance.
	 *
	 * @var Generator
	 */
	protected $faker;

	/**
	 * Constructor.
	 *
	 * @param Generator $generator Faker generator.
	 */
	public function __construct( Generator $generator ) {
		$this->faker = $generator;
	}

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return mixed The result. Can be anything.
	 */
	public function create( $args ) {
		global $current_site, $base;
		return Site::create(
			array_merge(
				[
					'domain'     => $current_site->domain,
					'path'       => $base . $this->faker->slug,
					'title'      => $this->faker->text,
					'network_id' => $current_site->id,
				],
				$args
			)
		)->core_object();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return mixed The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return \get_site( $object_id );
	}
}
