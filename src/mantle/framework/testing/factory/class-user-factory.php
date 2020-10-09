<?php
/**
 * User_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Factory;

use Faker\Generator;
use Mantle\Framework\Database\Model\User;

use function Mantle\Framework\Helpers\get_user_object;

/**
 * User Factory
 */
class User_Factory extends Factory {
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
	public function create( array $args = [] ): ?int {
		return User::create(
			array_merge(
				[
					'user_email' => $this->faker->email,
					'user_login' => $this->faker->userName,
					'user_pass'  => 'password',
				],
				$args
			)
		)->id();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_User|null
	 */
	public function get_object_by_id( int $object_id ) {
		return get_user_object( $object_id );
	}
}
