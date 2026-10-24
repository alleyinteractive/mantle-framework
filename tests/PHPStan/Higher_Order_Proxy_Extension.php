<?php
/**
 * Higher_Order_Proxy_Extension class file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\PHPStan;

use Mantle\Support\Higher_Order_Collection_Proxy;
use Mantle\Support\Higher_Order_Tap_Proxy;
use Mantle\Support\Higher_Order_When_Proxy;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Tell PHPStan that any method can be called on a higher order proxy.
 *
 * The proxies forward every method call to the underlying items or object
 * through `__call()`, which PHPStan does not resolve on its own.
 */
class Higher_Order_Proxy_Extension implements MethodsClassReflectionExtension {
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
		return in_array(
			$class_reflection->getName(),
			[
				Higher_Order_Collection_Proxy::class,
				Higher_Order_Tap_Proxy::class,
				Higher_Order_When_Proxy::class,
			],
			true,
		);
	}

	/**
	 * Get the method reflection.
	 *
	 * @param ClassReflection $class_reflection Class reflection.
	 * @param string          $method_name Method name.
	 */
	public function getMethod( ClassReflection $class_reflection, string $method_name ): MethodReflection {
		return $this->reflection_provider->getClass( Higher_Order_Proxy_Method::class )->getNativeMethod( 'call' );
	}
}
