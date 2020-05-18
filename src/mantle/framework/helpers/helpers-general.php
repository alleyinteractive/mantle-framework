<?php
/**
 * This file contains assorted helpers
 *
 * @package Mantle
 */

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment

namespace Mantle\Framework\Helpers;

use Countable;
use Exception;
use Mantle\Framework\Container\Container;
use Mantle\Framework\Database\Factory\Factory_Builder;
use Mantle\Framework\Support\Collection;
use Mantle\Framework\Support\Higher_Order_Tap_Proxy;
use Mantle\Framework\Database\Factory\Factory as MantleFactory;

/**
 * Determine if the given value is "blank".
 *
 * @param mixed $value Value to check.
 *
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
 * @param string|object $class Class or object to basename.
 *
 * @return string
 */
function class_basename( $class ) {
	$class = is_object( $class ) ? get_class( $class ) : $class;

	return basename( str_replace( '\\', '/', $class ) );
}

/**
 * Returns all traits used by a class, its parent classes and trait of their traits.
 *
 * @param object|string $class Class or object to analyze.
 *
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
 * @param mixed $value Value to collect.
 *
 * @return Collection
 */
function collect( $value = null ) {
	return new Collection( $value );
}

/**
 * Determine if a value is "filled".
 *
 * @param mixed $value Value to check.
 *
 * @return bool
 */
function filled( $value ) {
	return ! blank( $value );
}

/**
 * Get an item from an object using "dot" notation.
 *
 * @param object      $object Object from which to get an item.
 * @param string|null $key Key path at which to get the value.
 * @param mixed       $default Default value to return on failure.
 *
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
 * @param string $pattern Pattern for which to search.
 * @param array $replacements Strings in which to replace sequentially.
 * @param string $subject Subject in which to search/replace.
 *
 * @return string
 */
function preg_replace_array( $pattern, array $replacements, $subject ) {
	return preg_replace_callback(
		$pattern,
		function () use ( &$replacements ) {
			foreach ( $replacements as $key => $value ) {
				return array_shift( $replacements );
			}
		},
		$subject
	);
}

/**
 * Retry an operation a given number of times.
 *
 * @param int           $times Number of times to retry.
 * @param callable      $callback Callable to try.
 * @param int           $sleep Number of milliseconds to sleep between tries.
 * @param callable|null $when Callable against which to check the thrown
 *                                exception to determine if a retry should not
 *                                occur.
 *
 * @return mixed
 * @throws \Exception If the callable throws an exception, it is rethrown when
 *                    the retry limit is hit or when `$when` says so.
 */
function retry( $times, callable $callback, $sleep = 0, $when = null ) {
	$attempts = 0;

	// phpcs:ignore Generic.PHP.DiscourageGoto.Found
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

		// phpcs:ignore Generic.PHP.DiscourageGoto.Found
		goto beginning;
	}
}

/**
 * Call the given Closure with the given value then return the value.
 *
 * @param mixed         $value Value to provide to the callback and return.
 * @param callable|null $callback Callable to tap.
 *
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
 * @param mixed             $condition Condition to check.
 * @param \Throwable|string $exception Exception to throw.
 * @param array             ...$parameters Params to pass to a new $exception if
 *                                         $exception is a string (classname).
 *
 * @return mixed
 * @throws \Throwable `$exception` is thrown if `$condition` is not met.
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
 * @param mixed             $condition Condition to check.
 * @param \Throwable|string $exception Exception to throw.
 * @param array             ...$parameters Params to pass to a new $exception if
 *                                         $exception is a string (classname).
 *
 * @return mixed
 * @throws \Throwable `$exception` is thrown unless `$condition` is not met.
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
 * @param string $trait Trait to check.
 *
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
 * @param mixed    $value Value to check.
 * @param callable $callback Callable to pass `$value`.
 * @param mixed    $default Fallback if `$value` is not filled. May be a
 *                           callable which accepts `$value`, or it may be any
 *                           other value which is returned directly.
 *
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
 * @param mixed         $value Value to return.
 * @param callable|null $callback Callable to pass `$value` through.
 *
 * @return mixed
 */
function with( $value, callable $callback = null ) {
	return is_null( $callback ) ? $value : $callback( $value );
}

/**
 * Get the available container instance.
 *
 * @param string|null $abstract
 * @param array       $parameters
 *
 * @return mixed|Mantle\Framework\Container\Container
 * @throws \Mantle\Framework\Container\Binding_Resolution_Exception Binding resolution exception.
 */
function app( $abstract = null, array $parameters = [] ) {
	if ( is_null( $abstract ) ) {
		return Container::getInstance();
	}

	return Container::getInstance()->make( $abstract, $parameters );
}

/**
 * Create a model factory builder for a given class and amount.
 *
 * @param string $class
 * @param int    $amount
 *
 * @return Factory_Builder
 * @throws \Mantle\Framework\Container\Binding_Resolution_Exception Binding resolution exception.
 */
function factory( $class, $amount = null ) {
	$factory = app( MantleFactory::class );


	if ( isset( $amount ) && is_int( $amount ) ) {
		return $factory->of( $class )->times( $amount );
	}

	return $factory->of( $class );
}
