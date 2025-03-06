<?php
/**
 * User_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Mantle\Database\Model\User;

use function Mantle\Support\Helpers\get_user_object;
use function Mantle\Support\Helpers\stringable;

/**
 * User Factory
 *
 * @template TModel of \Mantle\Database\Model\User
 * @template TObject of \WP_User
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class User_Factory extends Factory {
	use Concerns\With_Meta;

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = User::class;

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		$first_name = $this->faker->firstName();
		$last_name  = $this->faker->lastName();

		return [
			'description' => $this->faker->sentence(),
			'first_name'  => $first_name,
			'last_name'   => $last_name,
			'role'        => 'subscriber',
			'user_email'  => $this->faker->email(),
			'user_login'  => stringable( "{$first_name} {$last_name}" )->slugify(),
			'user_pass'   => 'password',
			'user_url'    => $this->faker->url(),
		];
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_User|\Mantle\Database\Model\User|null
	 */
	public function get_object_by_id( int $object_id ) {
		return $this->as_models ? $this->model::find( $object_id ) : get_user_object( $object_id );
	}

	/**
	 * Create a user with a specific role.
	 *
	 * @param string $role The role to assign to the user.
	 */
	public function with_role( string $role ): static {
		return $this->state( [ 'role' => $role ] );
	}
}
