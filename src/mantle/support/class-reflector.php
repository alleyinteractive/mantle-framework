<?php
/**
 * Reflector class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;

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
			return null;
		}

		$name = $type->getName();

		if ( ! is_null( $class = $parameter->getDeclaringClass() ) ) {
			if ( 'self' === $name ) {
				return $class->getName();
			}

			if ( 'parent' === $name && $parent = $class->getParentClass() ) {
				return $parent->getName();
			}
		}

		return $name;
	}

	/**
	 * Get the class names of the given parameter's type, including union types.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @return array<string>
	 */
	public static function get_parameter_class_names( $parameter ): array {
		$type = $parameter->getType();

		if ( ! $type instanceof ReflectionUnionType ) {
			return array_filter( [ static::get_parameter_class_name( $parameter ) ] );
		}

		$union_types = [];

		foreach ( $type->getTypes() as $listed_type ) {
			if ( ! $listed_type instanceof ReflectionNamedType || $listed_type->isBuiltin() ) {
				continue;
			}

			$union_types[] = static::get_type_name( $parameter, $listed_type );
		}

		return array_filter( $union_types );
	}

	/**
	 * Get the given type's class name.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @param  \ReflectionNamedType $type
	 * @return string
	 */
	protected static function get_type_name( $parameter, $type ) {
		$name = $type->getName();

		if ( ! is_null( $class = $parameter->getDeclaringClass() ) ) {
			if ( 'self' === $name ) {
				return $class->getName();
			}

			if ( 'parent' === $name && $parent = $class->getParentClass() ) {
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
	 */
	public static function is_parameter_subclass_of( $parameter, $class_name ): bool {
		$param_class_name = static::get_parameter_class_name( $parameter );

		return $param_class_name && class_exists( $param_class_name ) && ( new ReflectionClass( $param_class_name ) )->isSubclassOf( $class_name );
	}

	/**
	 * Get the attributes for a class or method.
	 *
	 * @see https://www.php.net/manual/en/reflectionclass.getattributes.php
	 *
	 * @param  object|string     $class     The class name.
	 * @param  string            $method    The method name.
	 * @param  class-string|null $attribute The attribute name to filter by, or null for all attributes.
	 * @param  int               $flags     Flags to pass to getAttributes().
	 * @return array<\ReflectionAttribute>
	 */
	public static function get_attributes_for_method( object|string $class, string $method, ?string $attribute = null, int $flags = 0 ): array {
		$reflection = new ReflectionClass( $class );

		if ( ! $reflection->hasMethod( $method ) ) {
			return [];
		}

		return [
			...$reflection->getAttributes( $attribute, $flags ),
			...$reflection->getMethod( $method )->getAttributes( $attribute, $flags ),
		];
	}
}
