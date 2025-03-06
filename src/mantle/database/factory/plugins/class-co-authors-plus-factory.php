<?php
/**
 * Co_Authors_Plus_Factory class file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Plugins;

use InvalidArgumentException;
use Mantle\Database\Factory\Concerns\With_Meta;
use Mantle\Database\Factory\Factory;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Post;
use Mantle\Support\Pipeline;
use RuntimeException;

use function Mantle\Support\Helpers\get_post_object;
use function Mantle\Support\Helpers\stringable;

/**
 * Co Authors Guest Author Factory
 *
 * @template TModel of \Mantle\Database\Model\Post
 * @template TObject
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Co_Authors_Plus_Factory extends Factory {
	use With_Meta;

	/**
	 * Post type to use.
	 */
	public const POST_TYPE = 'guest-author';

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = Post::class;

	/**
	 * Pass along a user ID to associate with the guest author.
	 *
	 * @param int $user_id The user ID.
	 */
	public function with_linked_user( int $user_id ): static {
		return $this->state(
			[
				'user_id' => $user_id,
			]
		);
	}

	/**
	 * Noop.
	 *
	 * @throws InvalidArgumentException Thrown on use.
	 */
	public function as_models(): never {
		throw new InvalidArgumentException( 'Co-Authors Plus guest authors are not supported as models.' );
	}

	/**
	 * Model definition.
	 *
	 * @return array<string, string>
	 */
	public function definition(): array {
		return [];
	}

	/**
	 * Make the underlying guest author object.
	 *
	 * @throws RuntimeException If Co-Authors Plus is not loaded.
	 *
	 * @param array<mixed> $args Arguments to pass to the factory.
	 */
	public function make( array $args = [] ): Post {
		global $coauthors_plus;

		if ( ! isset( $coauthors_plus ) || ! $coauthors_plus instanceof \CoAuthors_Plus ) {
			throw new RuntimeException( 'Co-Authors Plus is not loaded. Ensure it is loaded when unit testing.' );
		}

		// Apply the factory definition to top of the middleware stack.
		$this->middleware->prepend( $this->apply_definition() );

		// Append the arguments passed to make() as the last state values to apply.
		$factory = $this->state( $args );

		return Pipeline::make()
			->send( [] )
			->through( $factory->middleware->all() )
			->then(
				function ( array $args ) {
					global $coauthors_plus;

					$user_id = $args['user_id'] ?? 0;
					$user    = null;

					if ( $user_id ) {
						$user = get_userdata( $user_id );

						if ( ! $user ) {
							throw new InvalidArgumentException( 'User not found: ' . $user_id );
						}

						$args['user_login']     = $user->user_login;
						$args['linked_account'] = $user->user_login;

						// Inherit data from the user if not passed.
						if ( empty( $args['first_name'] ) ) {
							$args['first_name'] = get_user_meta( $user_id, 'first_name', true );
						}

						if ( empty( $args['last_name'] ) ) {
							$args['last_name'] = get_user_meta( $user_id, 'last_name', true );
						}

						if ( empty( $args['display_name'] ) ) {
							$args['display_name'] = $user->display_name;
						}

						if ( empty( $args['user_email'] ) ) {
							$args['user_email'] = $user->user_email;
						}
					}

					if ( empty( $args['display_name'] ) ) {
						$args['first_name'] ??= $this->faker->firstName();
						$args['last_name']  ??= $this->faker->lastName();
						$args['display_name'] = $args['first_name'] . ' ' . $args['last_name'];
					}

					$args = array_merge(
						[
							'first_name' => stringable( $args['display_name'] )->explode( ' ' )->first(),
							'last_name'  => stringable( $args['display_name'] )->explode( ' ' )->last(),
							'user_email' => $this->faker->email(),
							'user_login' => stringable( $args['display_name'] )->slugify()->prepend( 'cap-' ),
						],
						$args
					);

					$author = $coauthors_plus->guest_authors->create( $args );

					if ( is_wp_error( $author ) ) {
						throw new Model_Exception( $author->get_error_message() );
					}

					return Post::for( self::POST_TYPE )->find( $author );
				}
			);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * Returning a model is not supported by this factory.
	 *
	 * @param int $object_id The object ID.
	 * @return \stdClass|null
	 * @phpstan-return TObject|null
	 */
	public function get_object_by_id( int $object_id ) {
		global $coauthors_plus;

		return $coauthors_plus->guest_authors->get_guest_author_by( 'ID', $object_id );
	}
}
