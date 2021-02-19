<?php
/**
 * WordPress_Action trait file.
 *
 * @package Mantle
 */

namespace Mantle\Events;

use Closure;
use Mantle\Framework\Contracts\Support\Arrayable;
use Mantle\Support\Enumerable;
use Mantle\Support\Reflector;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
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
	 * @return void
	 */
	public function action( string $action, callable $callback, int $priority = 10 ): void {
		\add_action( $action, $this->wrap_action_callback( $callback ), $priority, 99 );
	}

	/**
	 * Add a WordPress filter with type-hint support.
	 *
	 * @param string   $action Action to listen to.
	 * @param callable $callback Callback to invoke.
	 * @param int      $priority
	 * @return void
	 */
	public function filter( string $action, callable $callback, int $priority = 10 ): void {
		\add_filter( $action, $this->wrap_action_callback( $callback ), $priority, 99 );
	}

	/**
	 * Wrap the callback for an action with callback that will preserve type hints.
	 *
	 * @param callable $callback
	 * @return Closure
	 */
	protected function wrap_action_callback( callable $callback ): Closure {
		return function( ...$args ) use ( $callback ) {
			if ( is_array( $callback ) ) {
				$class      = new ReflectionClass( $callback[0] );
				$parameters = $class->getMethod( $callback[1] )->getParameters();
			} else {
				$parameters = ( new ReflectionFunction( $callback ) )->getParameters();
			}

			if ( empty( $parameters ) ) {
				return $callback( ...$args );
			}

			return $callback( ...$this->validate_arguments( $args, $parameters ) );
		};
	}

	/**
	 * Validate arguments for a type-hint
	 *
	 * @param array                 $arguments Arguments passed to the hook.
	 * @param ReflectionParameter[] $parameters parameters for the callback.
	 * @return array
	 */
	protected function validate_arguments( $arguments, array $parameters ) {
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

		if ( ! $type->isBuiltin() ) {
			$parameter_class = Reflector::get_parameter_class_name( $parameter );

			if ( Reflector::is_parameter_subclass_of( $parameter, Enumerable::class ) ) {
				return $parameter_class::make( $argument );
			}

			// Return the argument if the class matches the typehint.
			$class_name = Reflector::get_parameter_class_name( $parameter );
			if (
				$class_name
				&& ( is_object( $argument ) && get_class( $argument ) === $class_name || is_subclass_of( $argument, $class_name ) )
			) {
				return $argument;
			}

			/**
			 * Fire an event to allow a type-hint conversion to be added dynamically.
			 *
			 * For example, if you wanted to handle the type-hint conversion to `SomeClass`, one could
			 * listen for the `event-typehint:SomeClass` event to be fired. Returning a non-null value
			 * to that event will pass the argument down to the callback for the action/filter.
			 *
			 * @param mixed               $argument Argument value.
			 * @param ReflectionParameter $parameter Callback paramater.
			 */
			$modified_argument = $this->dispatch( 'event-typehint:' . $type->getName(), [ $argument, $parameter ], true );
			if ( $modified_argument ) {
				return $modified_argument;
			}

			// Handle gracefully in production.
			if ( $this->container->is_environment( 'production' ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					"Invalid type hinted parameter on callback: [$parameter_class]", // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'0.1.0'
				);

				// Pass to the container to resolve the object's arguments.
				return $this->container->make( $parameter_class, [ $parameter ] );
			}

			throw new RuntimeException( "Unknown type hinted parameter on callback: [$parameter_class]" );
		}

		// Ensure an 'Arrayable' interface is cast to an array properly.
		if ( 'array' === $type->getName() && $argument instanceof Arrayable ) {
			return $argument->to_array();
		}

		// Handle type casting internal arguments.
		$argument_type = gettype( $argument );

		if ( $argument_type === $type->getName() ) {
			return $argument;
		}

		settype( $argument, $type->getName() );
		return $argument;
	}
}
