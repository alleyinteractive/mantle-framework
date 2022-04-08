<?php
/**
 * Post_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Faker\Generator;
use Mantle\Database\Model\Post;

use function Mantle\Support\Helpers\get_post_object;

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
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	public function create( array $args = [] ): ?int {
		return Post::create(
			array_merge(
				[
					'content'   => trim( $this->faker->randomHtml() ),
					'excerpt'   => trim( $this->faker->paragraph( 2 ) ),
					'status'    => 'publish',
					'title'     => $this->faker->sentence(),
					'post_type' => $this->post_type,
				],
				$args
			)
		)->id();
	}

	/**
	 * Create a post with a thumbnail.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	public function create_with_thumbnail( array $args = [] ): ?int {
		$meta = $args['meta'] ?? [];

		$meta['_thumbnail_id'] = ( new Attachment_Factory( $this->faker ) )->create();

		return $this->create(
			array_merge(
				$args,
				[
					'meta' => $meta,
				]
			)
		);
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
