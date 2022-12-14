<?php
/**
 * With_Meta trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory\Concerns;

use Closure;

/**
 * Support model meta within the database factory
 *
 * @mixin \Mantle\Testing\Factory\Factory
 */
trait With_Meta {
	/**
	 * Create a new factory instance to create posts with a set of meta.
	 *
	 * @param array<string, mixed> $meta Meta to assign to the post.
	 * @return static
	 */
	public function with_meta( array $meta ) {
		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $meta ) {
				$args['meta'] = array_merge_recursive(
					$args['meta'] ?? [],
					$meta
				);

				return $next( $args );
			}
		);
	}
}
