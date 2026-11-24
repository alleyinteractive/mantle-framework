<?php
/**
 * With_Meta trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory\Concerns;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Database\Model_Meta;
use RuntimeException;

/**
 * Support model meta within the database factory
 *
 * @mixin \Mantle\Database\Factory\Factory
 */
trait With_Meta {
	/**
	 * Create a new factory instance to create objects with a set of meta.
	 *
	 * Accepts a single key/value pair, a map of meta, or several maps. A key
	 * that appears in more than one map is stored once per map, in argument
	 * order, which creates multiple meta entries for the same key:
	 *
	 *     ->with_meta(
	 *         [ 'featured' => '1' ],
	 *         [ 'line' => 'Line 1' ],
	 *         [ 'line' => 'Line 2' ],
	 *     )
	 *
	 * The first occurrence of a key is saved with the object. Later occurrences
	 * are added after the object is created, so hooks that fire during creation
	 * will not see them. Chained `with_meta()` calls keep merging into a single
	 * entry per key.
	 *
	 * @param array<string, mixed>|string $meta Meta to assign to the object, or a meta key.
	 * @param mixed                       $value Value for the meta key when `$meta` is a string. When
	 *                                           `$meta` is an array, an array here is a second meta map.
	 * @param array<string, mixed>        ...$more Additional meta maps.
	 * @return static
	 *
	 * @throws InvalidArgumentException When a meta key is combined with additional meta maps.
	 */
	public function with_meta( array|string $meta, mixed $value = '', array ...$more ) {
		if ( is_string( $meta ) ) {
			if ( [] !== $more ) {
				throw new InvalidArgumentException( 'Additional meta maps cannot be combined with a single meta key.' );
			}

			$maps = [ [ $meta => $value ] ];
		} else {
			$maps = [ $meta ];

			if ( is_array( $value ) ) {
				$maps[] = $value;
			}

			array_push( $maps, ...$more );
		}

		[ $first, $repeated ] = $this->split_meta_maps( $maps );

		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $first, $repeated ) {
				$args['meta'] = array_merge_recursive(
					$args['meta'] ?? [],
					$first
				);

				$model = $next( $args );

				if ( [] === $repeated ) {
					return $model;
				}

				if ( ! $model instanceof Model_Meta ) {
					throw new RuntimeException( 'Repeated meta keys require a model that supports meta.' );
				}

				foreach ( $repeated as [ $key, $meta_value ] ) {
					$model->add_meta( $key, $meta_value );
				}

				return $model;
			}
		);
	}

	/**
	 * Split meta maps into the first value for each key and the repeated values.
	 *
	 * @param array<int, array<string, mixed>> $maps Meta maps in argument order.
	 * @return array{0: array<string, mixed>, 1: array<int, array{0: string, 1: mixed}>}
	 */
	protected function split_meta_maps( array $maps ): array {
		$first    = [];
		$repeated = [];

		foreach ( $maps as $map ) {
			foreach ( $map as $key => $meta_value ) {
				if ( array_key_exists( $key, $first ) ) {
					$repeated[] = [ $key, $meta_value ];
				} else {
					$first[ $key ] = $meta_value;
				}
			}
		}

		return [ $first, $repeated ];
	}
}
