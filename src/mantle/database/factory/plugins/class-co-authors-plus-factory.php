<?php
namespace Mantle\Database\Factory\Plugins;

use Closure;
use Faker\Generator;
use Mantle\Database\Factory\Concerns\With_Meta;
use Mantle\Database\Factory\Factory;
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
	 * Constructor.
	 *
	 * @param Generator $faker Faker generator.
	 */
	public function __construct( Generator $faker ) {
		parent::__construct( $faker );

		// $this->middleware->push( function ( array $args, Closure $next ) {
		// 	// Check if a display name is provided.
		// 	$display_name = $args['post_title'] ?? $this->faker->name();

		// 	$args['post_title'] = $display_name;
		// 	$args['post_name']  = stringable( $display_name )->slugify()->prepend( 'cap-')
		// 	dd($args);
		// } );
	}

	/**
	 * Model definition.
	 *
	 * @return array<string, string>
	 */
	public function definition(): array
	{
		return [];
	}

	public function make( array $args = [] ): Post {
		global $coauthors_plus;

		if ( ! isset( $coauthors_plus ) || ! $coauthors_plus instanceof \CoAuthors_Plus ) {
			throw new RuntimeException( 'Co-Authors Plus is not loaded.' );
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

					if ( empty( $args['display_name'] ) ) {
						$args['first_name']   = $this->faker->firstName();
						$args['last_name']    = $this->faker->lastName();
						$args['display_name'] = $args['first_name'] . ' ' . $args['last_name'];
					}

					if ( empty( $args['user_email'] ) ) {
						$args['user_email'] = $this->faker->email();
					}

					if ( empty( $args['user_login'] ) ) {
						$args['user_login'] = stringable( $args['display_name'] )->slugify()->prepend( 'cap-' );
					}

					$author = $coauthors_plus->guest_authors->create( $args );

					dump("created author: {$author}", get_post($author));

					dd('model', Post::for( self::POST_TYPE ), Post::for( self::POST_TYPE )::find( $author->ID ));
				}
			);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return Post|WP_Post|null
	 * @phpstan-return TModel|TObject|null
	 */
	public function get_object_by_id( int $object_id ) {
		return $this->as_models
			? Post::for( self::POST_TYPE )::find( $object_id )
			: get_post_object( $object_id );
	}
}
