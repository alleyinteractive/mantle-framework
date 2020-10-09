<?php
/**
 * Post_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Factory;

use Faker\Generator;
use Mantle\Framework\Database\Model\Post;

use function Mantle\Framework\Helpers\get_post_object;

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
	 * @return int|null|
	 */
	public function create( array $args = [] ): ?int {
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
		)->id();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_Post|null
	 */
	public function get_object_by_id( int $object_id ) {
		return get_post_object( $object_id );
	}
}
