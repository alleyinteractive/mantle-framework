<?php
/**
 * Faker_Provider_Extension class file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\PHPStan;

use Faker\Generator;
use Mantle\Faker\Faker_Provider;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Expose the methods of the Mantle Faker provider on the Faker generator.
 *
 * Provider methods are resolved through `Generator::__call()`, which PHPStan
 * does not follow on its own.
 */
class Faker_Provider_Extension implements MethodsClassReflectionExtension {
	/**
	 * Constructor.
	 *
	 * @param ReflectionProvider $reflection_provider Reflection provider.
	 */
	public function __construct( private ReflectionProvider $reflection_provider ) {}

	/**
	 * Determine if the class has the method.
	 *
	 * @param ClassReflection $class_reflection Class reflection.
	 * @param string          $method_name Method name.
	 */
	public function hasMethod( ClassReflection $class_reflection, string $method_name ): bool {
		return Generator::class === $class_reflection->getName()
			&& $this->reflection_provider->getClass( Faker_Provider::class )->hasNativeMethod( $method_name );
	}

	/**
	 * Get the method reflection.
	 *
	 * @param ClassReflection $class_reflection Class reflection.
	 * @param string          $method_name Method name.
	 */
	public function getMethod( ClassReflection $class_reflection, string $method_name ): MethodReflection {
		return $this->reflection_provider->getClass( Faker_Provider::class )->getNativeMethod( $method_name );
	}
}
