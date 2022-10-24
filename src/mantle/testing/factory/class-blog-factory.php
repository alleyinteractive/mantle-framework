<?php
/**
 * Blog_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Faker\Generator;
use Mantle\Database\Model\Site;

use function Mantle\Support\Helpers\get_site_object;

/**
 * Blog Factory
 *
 * @template TObject
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
	 * @return int|null
	 */
	public function create( array $args = [] ) {
		global $current_site, $base;

		$args = array_merge(
			[
				'domain'     => $current_site->domain,
				'path'       => $base . $this->faker->slug(),
				'title'      => $this->faker->text(),
				'network_id' => $current_site->id,
			],
			$args
		);

		return $this->make( $args, Site::class )?->id();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_Site|null
	 */
	public function get_object_by_id( int $object_id ) {
		return get_site_object( $object_id );
	}
}
