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
	 * @param  array  $parameters
	 * @param  object $instance
	 * @param  string $method
	 * @return array
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
	 * @param  array                       $parameters
	 * @param  \ReflectionFunctionAbstract $reflector
	 * @return array
	 */
	public function resolve_method_dependencies( array $parameters, ReflectionFunctionAbstract $reflector ) {
		$instance_count = 0;

		$values = array_values( $parameters );

		$skippable_value   = new \stdClass();
		$method_parameters = [];
		$has_typehint      = false;

		foreach ( $reflector->getParameters() as $key => $parameter ) {
			if ( $parameter->getType() ) {
				$has_typehint = true;
			}

			$method_parameters[] = $parameter->getName();

			$instance = $this->transform_dependency( $parameter, $parameters, $skippable_value );

			if ( $instance !== $skippable_value ) {
				$instance_count++;

				$this->splice_into_parameters( $parameters, $key, $instance );
			} elseif ( ! isset( $values[ $key - $instance_count ] ) &&
				$parameter->isDefaultValueAvailable() ) {
				$this->splice_into_parameters( $parameters, $key, $parameter->getDefaultValue() );
			}
		}

		// Ensure the order of the parameters matches the order defined on the method.
		if ( $has_typehint ) {
			$parameters = array_replace(
				array_flip( $method_parameters ),
				$parameters,
			);
		}

		return $parameters;
	}

	/**
	 * Attempt to transform the given parameter into a class instance.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @param  array                $parameters
	 * @param  object               $skippable_value
	 * @return mixed
	 */
	protected function transform_dependency( ReflectionParameter $parameter, $parameters, $skippable_value ) {
		$class_name = Reflector::get_parameter_class_name( $parameter );

		// If the parameter has a type-hinted class, we will check to see if it is already in
		// the list of parameters. If it is we will just skip it as it is probably a model
		// binding and we do not want to mess with those; otherwise, we resolve it here.
		if ( $class_name && ! $this->already_in_parameters( $class_name, $parameters ) ) {
			return $parameter->isDefaultValueAvailable()
				? null
				: $this->container->make( $class_name );
		}

		return $skippable_value;
	}

	/**
	 * Determine if an object of the given class is in a list of parameters.
	 *
	 * @param  string $class
	 * @param  array  $parameters
	 * @return bool
	 */
	protected function already_in_parameters( $class, array $parameters ) {
		return ! is_null(
			Arr::first(
				$parameters,
				function ( $value ) use ( $class ) {
					return $value instanceof $class;
				}
			)
		);
	}

	/**
	 * Splice the given value into the parameter list.
	 *
	 * @param  array  $parameters
	 * @param  string $offset
	 * @param  mixed  $value
	 * @return void
	 */
	protected function splice_into_parameters( array &$parameters, $offset, $value ) {
		array_splice(
			$parameters,
			$offset,
			0,
			[ $value ]
		);
	}
}
