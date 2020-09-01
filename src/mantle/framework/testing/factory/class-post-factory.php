<?php
/**
 * Post_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Factory;

use Faker\Generator;
use Mantle\Framework\Database\Model\Post;

/**
 * Post Factory
 */
class Post_Factory extends Factory {
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
		return Post::create(
			array_merge(
				[
					'content'   => trim( $this->faker->randomHtml() ),
					'excerpt'   => trim( $this->faker->paragraph( 2 ) ),
					'status'    => 'publish',
					'title'     => $this->faker->sentence(),
					'post_type' => 'post',
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
		return \get_post( $object_id );
	}
}
