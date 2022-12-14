<?php
/**
 * User_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Closure;
use Faker\Generator;
use Mantle\Database\Model\User;

use function Mantle\Support\Helpers\get_user_object;

/**
 * User Factory
 *
 * @template TObject
 */
class User_Factory extends Factory {
	use Concerns\With_Meta;

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
		$first_name = $this->faker->firstName();
		$last_name  = $this->faker->lastName();

		$args = array_merge(
			[
				'description'  => $this->faker->sentence(),
				'display_name' => "{$first_name} {$last_name}",
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'role'         => 'subscriber',
				'user_email'   => $this->faker->email(),
				'user_login'   => $this->faker->userName(),
				'user_pass'    => 'password',
			],
			$args
		);

		return $this->make( $args, User::class )?->id();
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
