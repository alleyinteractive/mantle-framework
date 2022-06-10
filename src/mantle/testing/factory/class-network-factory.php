<?php
/**
 * Network_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Faker\Generator;

/**
 * Network Factory
 *
 * @template TObject
 */
class Network_Factory extends Factory {
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

		$this->network_id = 2;
	}

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments to pass to populate_network().
	 * @return int|null
	 */
	public function create( array $args = [] ): ?int {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( ! isset( $args['user'] ) ) {
			$email = $this->faker->email();
		} else {
			$email = get_userdata( $args['user'] )->user_email;
		}

		$args = array_merge(
			[
				'domain' => $this->faker->domainName(),
				'title'  => $this->faker->words(),
				'path'   => $this->faker->slug(),
			],
			$args
		);

		$network_id = $args['network_id'] ?? $this->network_id++;

		populate_network( $network_id, $args['domain'], $email, $args['title'], $args['path'], $args['subdomain_install'] ?? false );
		return (int) $network_id;
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_Network|null
	 */
	public function get_object_by_id( int $object_id ) {
		return get_network( $object_id );
	}
}
