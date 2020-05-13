<?php
/**
* This file contains assorted helpers
*
* @package Mantle
*/

namespace Mantle\Framework\Helpers;

use Countable;
use Exception;
use Mantle\Framework\Support\Higher_Order_Tap_Proxy;

/**
 * Determine if the given value is "blank".
 *
 * @param mixed $value
 * @return bool
 */
function blank( $value ) {
	if ( is_null( $value ) ) {
		return true;
	}

	if ( is_string( $value ) ) {
		return trim( $value ) === '';
	}

	if ( is_numeric( $value ) || is_bool( $value ) ) {
		return false;
	}

	if ( $value instanceof Countable ) {
		return count( $value ) === 0;
	}

	return empty( $value );
}

/**
 * Get the class "basename" of the given object / class.
 *
 * @param string|object $class
 * @return string
 */
function class_basename( $class ) {
	$class = is_object( $class ) ? get_class( $class ) : $class;

	return basename( str_replace( '\\', '/', $class ) );
}

/**
 * Returns all traits used by a class, its parent classes and trait of their traits.
 *
 * @param object|string $class
 * @return array
 */
function class_uses_recursive( $class ) {
	if ( is_object( $class ) ) {
		$class = get_class( $class );
	}

	$results = [];

	foreach ( array_reverse( class_parents( $class ) ) + [ $class => $class ] as $class ) {
		$results += trait_uses_recursive( $class );
	}

	return array_unique( $results );
}

/**
 * Create a collection from the given value.
 *
 * @param mixed $value
 * @return \Mantle\Framework\Support\Collection
 */
function collect( $value = null ) {
	return new Collection( $value );
}

/**
 * Fill in data where it's missing.
 *
 * @param mixed        $target
 * @param string|array $key
 * @param mixed        $value
 * @return mixed
 */
function data_fill( &$target, $key, $value ) {
	return data_set( $target, $key, $value, false );
}

/**
 * Determine if a value is "filled".
 *
 * @param mixed $value
 * @return bool
 */
function filled( $value ) {
	return ! blank( $value );
}

/**
 * Get an item from an object using "dot" notation.
 *
 * @param object      $object
 * @param string|null $key
 * @param mixed       $default
 * @return mixed
 */
function object_get( $object, $key, $default = null ) {
	if ( is_null( $key ) || trim( $key ) == '' ) {
		return $object;
	}

	foreach ( explode( '.', $key ) as $segment ) {
		if ( ! is_object( $object ) || ! isset( $object->{$segment} ) ) {
			return value( $default );
		}

		$object = $object->{$segment};
	}

	return $object;
}

/**
 * Replace a given pattern with each value in the array in sequentially.
 *
 * @param string $pattern
 * @param array  $replacements
 * @param string $subject
 * @return string
 */
function preg_replace_array( $pattern, array $replacements, $subject ) {
	return preg_replace_callback( $pattern, function () use ( &$replacements ) {
		foreach ( $replacements as $key => $value ) {
			return array_shift( $replacements );
		}
	}, $subject );
}

/**
 * Retry an operation a given number of times.
 *
 * @param int           $times
 * @param callable      $callback
 * @param int           $sleep
 * @param callable|null $when
 * @return mixed
 *
 * @throws \Exception
 */
function retry( $times, callable $callback, $sleep = 0, $when = null ) {
	$attempts = 0;

	beginning:
	$attempts ++;
	$times --;

	try {
		return $callback( $attempts );
	} catch ( Exception $e ) {
		if ( $times < 1 || ( $when && ! $when( $e ) ) ) {
			throw $e;
		}

		if ( $sleep ) {
			usleep( $sleep * 1000 );
		}

		goto beginning;
	}
}

/**
 * Call the given Closure with the given value then return the value.
 *
 * @param mixed         $value
 * @param callable|null $callback
 * @return mixed
 */
function tap( $value, $callback = null ) {
	if ( is_null( $callback ) ) {
		return new Higher_Order_Tap_Proxy( $value );
	}

	$callback( $value );

	return $value;
}

/**
 * Throw the given exception if the given condition is true.
 *
 * @param mixed             $condition
 * @param \Throwable|string $exception
 * @param array             ...$parameters
 * @return mixed
 *
 * @throws \Throwable
 */
function throw_if( $condition, $exception, ...$parameters ) {
	if ( $condition ) {
		throw ( is_string( $exception ) ? new $exception( ...$parameters ) : $exception );
	}

	return $condition;
}

/**
 * Throw the given exception unless the given condition is true.
 *
 * @param mixed             $condition
 * @param \Throwable|string $exception
 * @param array             ...$parameters
 * @return mixed
 *
 * @throws \Throwable
 */
function throw_unless( $condition, $exception, ...$parameters ) {
	if ( ! $condition ) {
		throw ( is_string( $exception ) ? new $exception( ...$parameters ) : $exception );
	}

	return $condition;
}

/**
 * Returns all traits used by a trait and its traits.
 *
 * @param string $trait
 * @return array
 */
function trait_uses_recursive( $trait ) {
	$traits = class_uses( $trait );

	foreach ( $traits as $trait ) {
		$traits += trait_uses_recursive( $trait );
	}

	return $traits;
}

/**
 * Transform the given value if it is present.
 *
 * @param mixed    $value
 * @param callable $callback
 * @param mixed    $default
 * @return mixed|null
 */
function transform( $value, callable $callback, $default = null ) {
	if ( filled( $value ) ) {
		return $callback( $value );
	}

	if ( is_callable( $default ) ) {
		return $default( $value );
	}

	return $default;
}

/**
 * Return the given value, optionally passed through the given callback.
 *
 * @param mixed         $value
 * @param callable|null $callback
 * @return mixed
 */
function with( $value, callable $callback = null ) {
	return is_null( $callback ) ? $value : $callback( $value );
}
