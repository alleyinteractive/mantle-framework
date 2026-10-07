<?php
/**
 * WordPress_Action trait file.
 *
 * @package Mantle
 */

namespace Mantle\Events;

use Closure;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Support\Enumerable;
use Mantle\Support\Reflector;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

/**
 * Extend the event dispatcher to allow for WordPress action/filter usage with
 * type hints. In non-production environments it will throw an error when it is unable
 * to translate a parameter. On production, it will handle it gracefully but throw a
 * _doing_it_wrong() notice.
 */
trait WordPress_Action {
	/**
	 * Add a WordPress action with type-hint support.
	 *
	 * @param string   $action Action to listen to.
	 * @param callable $callback Callback to invoke.
	 * @param int      $priority
	 */
	public function action( string $action, callable $callback, int $priority = 10 ): void {
		\add_action( $action, $this->create_action_callback( $callback ), $priority, 99 );
	}

	/**
	 * Dispatch an action if a condition resolves true.
	 *
	 * @param callable|bool $condition Condition to check.
	 * @param string        $action Action to invoke.
	 * @param callable      $callback Callback to invoke.
	 * @param int           $priority Action priority.
	 */
	public function action_if( $condition, string $action, callable $callback, int $priority = 10 ): void {
		if ( is_callable( $condition ) ) {
			$condition = $condition();
		}

		if ( $condition ) {
			$this->action( $action, $callback, $priority );
		}
	}

	/**
	 * Add a WordPress filter with type-hint support.
	 *
	 * @param string   $action Action to listen to.
	 * @param callable $callback Callback to invoke.
	 * @param int      $priority
	 */
	public function filter( string $action, callable $callback, int $priority = 10 ): void {
		\add_filter( $action, $this->create_action_callback( $callback ), $priority, 99 );
	}

	/**
	 * Wrap the callback for an action with callback that will preserve type hints.
	 *
	 * @param callable $callback
	 */
	protected function create_action_callback( callable $callback ): Closure {
		return function ( ...$args ) use ( $callback ) {
			if ( is_array( $callback ) ) {
				try {
					$reflection = ( new ReflectionClass( $callback[0] ) )->getMethod( $callback[1] );
				} catch ( ReflectionException $e ) {
					// Pass through if Reflection is unable to find the class and/or methods.
					unset( $e );
					return $callback( ...$args );
				}
			} elseif ( $callback instanceof Closure ) {
				$reflection = new ReflectionFunction( $callback );
			} else {
				throw new RuntimeException( 'Unsupported callback type: ' . get_debug_type( $callback ) );
			}

			$parameters = $reflection->getParameters();

			$result = empty( $parameters )
				? $callback( ...$args )
				: $callback( ...$this->validate_arguments( $args, $parameters ) );

			return $this->preserve_filtered_value( $reflection, $result, $args );
		};
	}

	/**
	 * Keep the filtered value intact when a listener does not return one.
	 *
	 * Events are dispatched as WordPress filters, so a listener's return value
	 * becomes the next listener's first argument. A `void` listener must not
	 * replace that value with null, and a listener for an object event must not
	 * replace the event with whatever its last expression happened to evaluate to.
	 *
	 * @param ReflectionFunctionAbstract $reflection Listener reflection.
	 * @param mixed                      $result Listener return value.
	 * @param array<mixed>               $args Arguments the listener was called with.
	 */
	protected function preserve_filtered_value( ReflectionFunctionAbstract $reflection, mixed $result, array $args ): mixed {
		$return_type  = $reflection->getReturnType();
		$returns_void = $return_type instanceof ReflectionNamedType && in_array( $return_type->getName(), [ 'void', 'never' ], true );

		if ( $returns_void ) {
			return $args[0] ?? null;
		}

		if ( isset( $args[0] ) && is_object( $args[0] ) && ! is_object( $result ) ) {
			return $args[0];
		}

		return $result;
	}

	/**
	 * Validate arguments for a type-hint
	 *
	 * @param array<mixed>          $arguments Arguments passed to the hook.
	 * @param ReflectionParameter[] $parameters parameters for the callback.
	 * @return array<mixed>
	 */
	protected function validate_arguments( array $arguments, array $parameters ): array {
		foreach ( $arguments as $i => &$argument ) {
			$parameter = $parameters[ $i ] ?? null;
			if ( ! $parameter ) {
				continue;
			}

			$argument = $this->validate_argument_type( $argument, $parameter );
		}

		return $arguments;
	}

	/**
	 * Validate the argument type matches the expected type-hinted parameter.
	 *
	 * @param mixed               $argument Argument value.
	 * @param ReflectionParameter $parameter Callback parameter.
	 * @return mixed
	 *
	 * @throws RuntimeException Thrown when a non-builtin type-hint cannot be translated properly.
	 */
	protected function validate_argument_type( $argument, ReflectionParameter $parameter ) {
		$type = $parameter->getType();
		if ( ! $type ) {
			return $argument;
		}

		if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
			$parameter_class = Reflector::get_parameter_class_name( $parameter );

			if ( Reflector::is_parameter_subclass_of( $parameter, Enumerable::class ) ) {
				return $parameter_class::make( $argument ); // @phpstan-ignore-line
			}

			// Return the argument if the class matches the typehint.
			$class_name = Reflector::get_parameter_class_name( $parameter );
			if (
				$class_name
				&& ( is_object( $argument ) && ( $argument::class === $class_name || is_subclass_of( $argument, $class_name ) ) ) // @phpstan-ignore-line always true
			) {
				return $argument;
			}

			/**
			 * Fire an event to allow a type-hint conversion to be added dynamically.
			 *
			 * For example, if you wanted to handle the type-hint conversion to `SomeClass`, one could
			 * listen for the `mantle-typehint-resolve:SomeClass` event to be fired. Returning a non-null value
			 * to that event will pass the argument down to the callback for the action/filter.
			 *
			 * @param mixed               $argument Argument value.
			 * @param ReflectionParameter $parameter Callback parameter.
			 */
			$modified_argument = $this->dispatch( 'mantle-typehint-resolve:' . $type->getName(), null, $argument, $parameter );

			if ( $modified_argument ) {
				return $modified_argument;
			}

			return $this->container->make( $parameter_class, [ $parameter ] ); // @phpstan-ignore-line type
		}

		// Ensure an 'Arrayable' interface is cast to an array properly.
		if ( $type instanceof ReflectionNamedType && 'array' === $type->getName() && $argument instanceof Arrayable ) {
			return $argument->to_array();
		}

		// Handle type casting internal arguments.
		$argument_type = gettype( $argument );

		if ( ! $type instanceof ReflectionNamedType ) {
			throw new RuntimeException( $type::class . ' is not a supported type-hint.' );
		}

		if ( $argument_type === $type->getName() ) {
			return $argument;
		}

		settype( $argument, $type->getName() );
		return $argument;
	}
}
