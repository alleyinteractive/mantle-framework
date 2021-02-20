<?php
/**
 * Reflector class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use ReflectionClass;
use ReflectionNamedType;

/**
 * Reflector Support
 */
class Reflector {

	/**
	 * Get the class name of the given parameter's type, if possible.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @return string|null
	 */
	public static function get_parameter_class_name( $parameter ) {
		$type = $parameter->getType();

		if ( ! $type instanceof ReflectionNamedType || $type->isBuiltin() ) {
			return;
		}

		// $name = $type->getName();

		// if ( 'self' === $name ) {
		// 	return $parameter->getDeclaringClass()->getName();
		// }

		$name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

		return $name;
	}

	/**
	 * Determine if the parameter's type is a subclass of the given type.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @param  string               $class_name
	 * @return bool
	 */
	public static function is_parameter_subclass_of( $parameter, $class_name ) {
		$param_class_name = static::get_parameter_class_name( $parameter );

		return ( $param_class_name && class_exists( $param_class_name ) )
			? ( new ReflectionClass( $param_class_name ) )->isSubclassOf( $class_name )
			: false;
	}
}
