<?php

namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Support\Arr;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

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
		$instanceCount = 0;

		$values = array_values( $parameters );

		$skippableValue = new \stdClass();

		foreach ( $reflector->getParameters() as $key => $parameter ) {
			$instance = $this->transform_dependency( $parameter, $parameters, $skippableValue );

			if ( $instance !== $skippableValue ) {
				$instanceCount++;

				$this->splice_into_parameters( $parameters, $key, $instance );
			} elseif ( ! isset( $values[ $key - $instanceCount ] ) &&
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
	 * @param  array                $parameters
	 * @param  object               $skippableValue
	 * @return mixed
	 */
	protected function transform_dependency( ReflectionParameter $parameter, $parameters, $skippableValue ) {
		$class = $parameter->getClass();

		// If the parameter has a type-hinted class, we will check to see if it is already in
		// the list of parameters. If it is we will just skip it as it is probably a model
		// binding and we do not want to mess with those; otherwise, we resolve it here.
		if ( $class && ! $this->already_in_parameters( $class->name, $parameters ) ) {
			return $parameter->isDefaultValueAvailable()
						? null
						: $this->container->make( $class->name );
		}

		return $skippableValue;
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
			array( $value )
		);
	}
}
