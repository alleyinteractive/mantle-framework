<?php
/**
 * With_Guest_Authors trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Concerns;

use stdClass;
use WP_User;

use function Mantle\Support\Helpers\collect;

/**
 * Manage Co-Authors Plus guest authors on posts.
 *
 * @mixin \Mantle\Database\Factory\Post_Factory
 */
trait With_Guest_Authors {
	/**
	 * Add a Co Authors Plus Guest Author or User to a post.
	 *
	 * @throws \RuntimeException If Co-Authors Plus is not installed or initialized.
	 *
	 * @param int|stdClass|WP_User ...$authors The guest author ID or object.
	 */
	public function with_cap_authors( ...$authors ): static {
		global $coauthors_plus;

		if ( ! class_exists( \CoAuthors_Guest_Authors::class ) ) {
			throw new \RuntimeException( 'Co-Authors Plus is not installed.' );
		}

		if ( ! isset( $coauthors_plus ) || ! $coauthors_plus instanceof \CoAuthors_Plus ) {
			throw new \RuntimeException( 'Co-Authors Plus is not loaded. Ensure it is loaded when unit testing.' );
		}

		$authors = collect( $authors )
			->map( fn ( $author ) => $this->resolve_guest_author( $author ) )
			->filter()
			->values();

		if ( ! $authors->is_empty() ) {
			return $this->with_terms( $authors->all() );
		}

		return $this;
	}

	/**
	 * Resolve the guest author to the underlying term ID for the Guest Author post.
	 *
	 * @param int|WP_User|stdClass $author The guest author ID/object or user object.
	 */
	protected function resolve_guest_author( int|WP_User|stdClass $author ): ?int {
		global $coauthors_plus;

		if ( $author instanceof WP_User ) {
			$term = $coauthors_plus->get_author_term( $author );

			if ( ! $term ) {
				$term = $coauthors_plus->update_author_term( $author );
			}

			return $term ? $term->term_id : null;
		}

		if ( ! is_object( $author ) ) {
			$author = $coauthors_plus->guest_authors->get_guest_author_by( 'ID', $author );

			if ( ! $author ) {
				return null;
			}
		}

		$term = $coauthors_plus->get_author_term( $author );

		return $term ? $term->term_id : null;
	}
}
