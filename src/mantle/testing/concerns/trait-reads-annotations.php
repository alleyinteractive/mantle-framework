<?php
/**
 * Reads_Annotations trait file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

use Mantle\Support\Reflector;
use PHPUnit\Metadata\Annotation\Parser\DocBlock;
use PHPUnit\Metadata\Annotation\Parser\Registry;
use PHPUnit\Runner\Version;
use PHPUnit\Util\Test;
use ReflectionClass;

/**
 * Read annotations for testing that supports multiple versions of PHPUnit.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait Reads_Annotations {
	/**
	 * Read docblock annotations for the current test case and method.
	 */
	public function get_annotations_for_method(): array {
		// Use the PHPUnit 10.x method if available.
		if ( class_exists( Registry::class ) && class_exists( DocBlock::class ) ) {
			$registry = Registry::getInstance();

			return [
				'class'  => $registry->forClassName( static::class )->symbolAnnotations(),
				'method' => $registry->forMethod( static::class, $this->name() )->symbolAnnotations(),
			];
		}

		// If we are using PHPUnit 12.0.0 or greater, we can bail because
		// annotations are no longer supported. Attributes must be used instead.
		return [];
	}

	/**
	 * Read the attributes for the current test case and method.
	 *
	 * @template T of object
	 *
	 * @param class-string|null $name Filter the results to include only ReflectionAttribute instances for attributes matching this class name.
	 * @param int               $flags Flags to pass to getAttributes().
	 * @param bool              $inherit Whether to include attributes from parent classes.
	 * @return array<\ReflectionAttribute>
	 *
	 * @phpstan-param class-string<T>|null $name
	 * @phpstan-return ($name is null ? array<\ReflectionAttribute> : array<\ReflectionAttribute<T>>)
	 */
	public function get_attributes_for_method( ?string $name = null, int $flags = 0, bool $inherit = true ): array {
		$class = new ReflectionClass( $this );

		if ( method_exists( $this, 'name' ) ) { // @phpstan-ignore-line function.alreadyNarrowedType
			$method = $class->getMethod( $this->name() );
		} elseif ( isset( $this->name ) ) {
			$method = $class->getMethod( $this->name );
		} else {
			trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				'Unable to read attributes for test method. Please file an issue with https://github.com/alleyinteractive/mantle-framework',
			);

			return [];
		}

		return [
			...Reflector::get_attributes_for_class( $this, $name, $flags, $inherit ),
			...$method->getAttributes( $name, $flags ),
		];
	}

	/**
	 * Check if the method has an attribute of a given name.
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $name The name of the method to check.
	 */
	public function method_has_attribute( string $name ): bool {
		return ! empty( $this->get_attributes_for_method( $name ) );
	}
}
