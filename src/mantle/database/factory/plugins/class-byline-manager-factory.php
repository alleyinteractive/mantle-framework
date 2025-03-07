<?php
/**
 * Byline_Manager_Factory class file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Plugins;

use Byline_Manager\Models\Profile;
use Mantle\Database\Factory\Concerns\With_Meta;
use Mantle\Database\Factory\Factory;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Post;
use Mantle\Support\Pipeline;
use RuntimeException;

use const Byline_Manager\PROFILE_POST_TYPE;

/**
 * Byline Manager Factory
 *
 * @template TModel of \Mantle\Database\Model\Post
 * @template TObject
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Byline_Manager_Factory extends Factory {
	use With_Meta;

	/**
	 * Post type to use.
	 */
	public const POST_TYPE = 'profile';

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = Post::class;

	/**
	 * Get the Byline Manager profile by user ID.
	 *
	 * Profile::get_by_user_id() is not finished yet in the plugin so we have to
	 * make this meta query manually.
	 *
	 * @throws RuntimeException If Byline Manager is not installed.
	 *
	 * @param int  $user_id The user ID.
	 * @param bool $create Whether to create the profile if it doesn't exist.
	 */
	public static function get_byline_manager_profile_by_user_id( int $user_id, bool $create = false ): ?Profile {
		$profiles = get_posts( [ // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
			'post_status'      => 'publish',
			'post_type'        => PROFILE_POST_TYPE,
			'posts_per_page'   => 1,
			'meta_query'       => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => 'user_id',
					'value' => $user_id,
				],
			],
			'suppress_filters' => false,
		] );

		if ( ! empty( $profiles ) ) {
			return new Profile( array_shift( $profiles ) );
		}

		if ( $create ) {
			$profile = Profile::create_from_user( $user_id );

			if ( is_wp_error( $profile ) ) {
				throw new RuntimeException( 'Error creating profile: ' . $profile->get_error_message() );
			}

			return $profile;
		}

		return null;
	}

	/**
	 * Pass along a user ID to associate with the profile.
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
	 * Model definition.
	 *
	 * @return array<string, string>
	 */
	public function definition(): array {
		return [];
	}

	/**
	 * Make the underlying profile object.
	 *
	 * @throws RuntimeException If Byline Manager is not loaded.
	 *
	 * @param array<mixed> $args Arguments to pass to the factory.
	 */
	public function make( array $args = [] ): Post {
		if ( ! class_exists( Profile::class ) ) {
			throw new RuntimeException( 'Byline Manager is not installed.' );
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
					if ( ! empty( $args['user_id'] ) ) {
						$profile = self::get_byline_manager_profile_by_user_id( $args['user_id'], create: true );

						if ( is_wp_error( $profile ) ) {
							throw new Model_Exception( 'Error creating profile: ' . $profile->get_error_message() );
						}

						return Post::for( self::POST_TYPE )->find( $profile->post->ID );
					} else {
						// Inherit the display name as the post title.
						if ( ! empty( $args['display_name'] ) ) {
							$args['post_title'] = $args['display_name'];
						} elseif ( empty( $args['post_title'] ) ) {
							$args['post_title'] = $this->faker->firstName() . ' ' . $this->faker->lastName();
						}

						$profile = Profile::create( $args );

						if ( is_wp_error( $profile ) ) {
							throw new Model_Exception( 'Error creating profile: ' . $profile->get_error_message() );
						}

						[ $first, $last ] = explode( ' ', (string) $profile->post->post_title );

						update_post_meta( $profile->post->ID, 'first_name', $first );
						update_post_meta( $profile->post->ID, 'last_name', $last );
					}

					return Post::for( self::POST_TYPE )->find( $profile->post->ID );
				}
			);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * Returning a model is not supported by this factory.
	 *
	 * @param int $object_id The object ID.
	 * @return Profile|null
	 * @phpstan-return TObject|null
	 */
	public function get_object_by_id( int $object_id ) {
		return Profile::get_by_post( $object_id ) ?: null;
	}
}
