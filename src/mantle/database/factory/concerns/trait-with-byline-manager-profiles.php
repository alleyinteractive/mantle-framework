<?php
/**
 * With_Byline_Manager_Profiles trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Concerns;

use Byline_Manager\Models\Profile;
use Byline_Manager\Utils;
use Closure;
use Mantle\Database\Factory\Plugins\Byline_Manager_Factory;
use WP_User;

use function Mantle\Support\Helpers\collect;

/**
 * Manage Byline Manager authors on posts.
 *
 * @phpstan-type BylineManagerEntry array{type: string, attrs: array<string, mixed>}
 *
 * @mixin \Mantle\Database\Factory\Post_Factory
 */
trait With_Byline_Manager_Profiles {
	/**
	 * Add a bylines to a post with Byline Manager.
	 *
	 * @throws \RuntimeException If Byline Manager is not installed or initialized.
	 *
	 * @param string|int|Profile|WP_User ...$authors The profile ID/object, WP_User object, or text string.
	 */
	public function with_byline_manager_authors( ...$authors ): static {
		if ( ! class_exists( Profile::class ) ) {
			throw new \RuntimeException( 'Byline Manager is not installed.' );
		}

		return $this->with_middleware( function ( array $args, Closure $next ) use ( $authors ) {
			/** @var \Mantle\Database\Model\Post $profile */
			$profile = $next( $args );

			Utils::set_post_byline(
				$profile->id(),
				[
					'byline_entries' => collect( $authors )
						->map( $this->resolve_byline_manager_entry( ... ) )
						->filter()
						->values()
						->all(),
				],
			);

			return $profile;
		} );
	}

	/**
	 * Resolve the author to the underlying term ID for the Profile.
	 *
	 * @param string|int|Profile|WP_User $item The profile ID/object, WP_User object, or text string.
	 * @return array
	 * @phpstan-return BylineManagerEntry
	 */
	protected function resolve_byline_manager_entry( string|int|WP_User|Profile $item ): ?array {
		if ( is_string( $item ) ) {
			return [
				'type' => 'text',
				'atts' => [
					'text' => $item,
				],
			];
		}

		if ( is_int( $item ) || $item instanceof Profile ) {
			$profile = $item instanceof Profile ? $item : Profile::get_by_post( $item );
		} elseif ( $item instanceof WP_User ) {
			$profile = Byline_Manager_Factory::get_byline_manager_profile_by_user_id( $item->ID, create: true );
		}

		if ( empty( $profile ) ) {
			return null;
		}

		return [
			'type' => 'byline_id',
			'atts' => [
				'byline_id' => $profile->byline_id,
			],
		];
	}
}
