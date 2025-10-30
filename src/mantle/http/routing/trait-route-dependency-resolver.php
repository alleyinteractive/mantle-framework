<?php
/**
 * Route_Dependency_Resolver trait file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Http\Routing;

use Mantle\Support\Arr;
use Mantle\Support\Reflector;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Route Method Dependency Resolver
 */
trait Route_Dependency_Resolver {

	/**
	 * Resolve the object method's type-hinted dependencies.
	 *
	 * @param  array<mixed>  $parameters
	 * @param  object $instance
	 * @param  string $method
	 * @return array<mixed>
	 */
	protected function resolve_class_method_dependencies( array $parameters, $instance, $method ) {
		if ( ! method_exists( $instance, $method ) ) {
			return $parameters;
		}

		return $this->resolve_method_dependencies(
			$parameters,
			new ReflectionMethod( $instance, $method )
		);
	}

	/**
	 * Resolve the given method's type-hinted dependencies.
	 *
	 * @param  array<mixed>                       $parameters
	 * @param  \ReflectionFunctionAbstract $reflector
	 * @return array<mixed>
	 */
	public function resolve_method_dependencies( array $parameters, ReflectionFunctionAbstract $reflector ): array {
		$instance_count = 0;

		$values = array_values( $parameters );

		$skippable_value = new \stdClass();

		foreach ( $reflector->getParameters() as $key => $parameter ) {
			$instance = $this->transform_dependency( $parameter, $parameters, $skippable_value );

			if ( $instance !== $skippable_value ) {
				$instance_count++;

				$this->splice_into_parameters( $parameters, $key, $instance );
			} elseif ( ! isset( $values[ $key - $instance_count ] ) &&
				$parameter->isDefaultValueAvailable() ) {
				$this->splice_into_parameters( $parameters, $key, $parameter->getDefaultValue() );
			}
		}

		return $parameters;
	}

	/**
	 * Attempt to transform the given parameter into a class instance.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @param  array<mixed>         $parameters
	 * @param  object               $skippable_value
	 */
	protected function transform_dependency( ReflectionParameter $parameter, $parameters, $skippable_value ): mixed {
		$class_name = Reflector::get_parameter_class_name( $parameter );

		// If the parameter has a type-hinted class, we will check to see if it is already in
		// the list of parameters. If it is we will just skip it as it is probably a model
		// binding and we do not want to mess with those; otherwise, we resolve it here.
		if ( $class_name && class_exists( $class_name ) && ! $this->already_in_parameters( $class_name, $parameters ) ) {
			$is_enum = ( new ReflectionClass( $class_name ) )->isEnum();

			return $parameter->isDefaultValueAvailable()
				? ( $is_enum ? $parameter->getDefaultValue() : null )
				: $this->container->make( $class_name );
		}

		return $skippable_value;
	}

	/**
	 * Determine if an object of the given class is in a list of parameters.
	 *
	 * @param  class-string $class
	 * @param  array<mixed>        $parameters
	 */
	protected function already_in_parameters( string $class, array $parameters ): bool {
		return ! is_null(
			Arr::first(
				$parameters,
				fn ( $value ) => $value instanceof $class,
			)
		);
	}

	/**
	 * Splice the given value into the parameter list.
	 *
	 * @param  array<mixed>  $parameters
	 * @param  int    $offset
	 * @param  mixed  $value
	 * @return void
	 */
	protected function splice_into_parameters( array &$parameters, int $offset, mixed $value ) {
		array_splice(
			$parameters,
			$offset,
			0,
			[ $value ]
		);
	}
}
