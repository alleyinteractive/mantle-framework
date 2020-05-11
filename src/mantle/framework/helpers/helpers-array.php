<?php
/**
 * Array helpers.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Helpers;

use Mantle\Framework\Support;

/**
 * Return the default value of the given value.
 *
 * @param mixed $value Value to check.
 * @return mixed
 */
function value( $value ) {
	return $value instanceof \Closure ? $value() : $value;
}

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param  mixed                 $target Target to get from.
 * @param  string|array|int|null $key Key to retrieve.
 * @param  mixed                 $default Default value.
 * @return mixed
 */
function data_get( $target, $key, $default = null ) {
	if ( is_null( $key ) ) {
		return $target;
	}

	$key = is_array( $key ) ? $key : explode( '.', $key );

	foreach ( $key as $i => $segment ) {
		unset( $key[ $i ] );

		if ( is_null( $segment ) ) {
			return $target;
		}

		if ( '*' === $segment ) {
			if (
				class_exists( 'Mantle\Framework\Support\Collection' )
				&& $target instanceof \Mantle\Framework\Support\Collection
			) {
					$target = $target->all();
			} elseif ( ! is_array( $target ) ) {
					return value( $default );
			}

			$result = [];

			foreach ( $target as $item ) {
				$result[] = data_get( $item, $key );
			}

			return in_array( '*', $key ) ? Support\Arr::collapse( $result ) : $result;
		}

		if ( Support\Arr::accessible( $target ) && Support\Arr::exists( $target, $segment ) ) {
			$target = $target[ $segment ];
		} elseif ( is_object( $target ) && isset( $target->{$segment} ) ) {
			$target = $target->{$segment};
		} else {
			return value( $default );
		}
	}

	return $target;
}

/**
 * Set an item on an array or object using dot notation.
 *
 * @param  mixed        $target Array to update.
 * @param  string|array $key Key to set.
 * @param  mixed        $value Value to set.
 * @param  bool         $overwrite Flag to overwrite the existing value.
 * @return mixed
 */
function data_set( &$target, $key, $value, $overwrite = true ) {
	$segments = is_array( $key ) ? $key : explode( '.', $key );
	$segment  = array_shift( $segments );

	if ( '*' === $segment ) {
		if ( ! Support\Arr::accessible( $target ) ) {
			$target = [];
		}

		if ( $segments ) {
			foreach ( $target as &$inner ) {
				data_set( $inner, $segments, $value, $overwrite );
			}
		} elseif ( $overwrite ) {
			foreach ( $target as &$inner ) {
				$inner = $value;
			}
		}
	} elseif ( Support\Arr::accessible( $target ) ) {
		if ( $segments ) {
			if ( ! Support\Arr::exists( $target, $segment ) ) {
				$target[ $segment ] = [];
			}

			data_set( $target[ $segment ], $segments, $value, $overwrite );
		} elseif ( $overwrite || ! Support\Arr::exists( $target, $segment ) ) {
			$target[ $segment ] = $value;
		}
	} elseif ( is_object( $target ) ) {
		if ( $segments ) {
			if ( ! isset( $target->{$segment} ) ) {
				$target->{$segment} = [];
			}

			data_set( $target->{$segment}, $segments, $value, $overwrite );
		} elseif ( $overwrite || ! isset( $target->{$segment} ) ) {
			$target->{$segment} = $value;
		}
	} else {
		$target = [];

		if ( $segments ) {
			data_set( $target[ $segment ], $segments, $value, $overwrite );
		} elseif ( $overwrite ) {
			$target[ $segment ] = $value;
		}
	}

	return $target;
}
