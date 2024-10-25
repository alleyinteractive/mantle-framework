<?php
/**
 * Interacts_With_Attributes trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

/**
 * Trait to allow simple callbacks when a registered attribute is added to a
 * test class/method.
 */
trait Interacts_With_Attributes {
	use Reads_Annotations;

	/**
	 * Attribute callbacks to interact with.
	 *
	 * @var array<class-string, callable(\ReflectionAttribute $attribute): mixed>
	 */
	private array $attribute_callbacks = [];

	/**
	 * Interact with attributes set up.
	 */
	protected function interacts_with_attributes_set_up(): void {
		foreach ( $this->attribute_callbacks as $attribute_class => $callback ) {
			foreach ( $this->get_attributes_for_method( $attribute_class ) as $attribute ) {
				$callback( $attribute );
			}
		}
	}

	/**
	 * Interact with attributes tear down.
	 */
	protected function interacts_with_attributes_tear_down(): void {
		$this->attribute_callbacks = [];
	}

	/**
	 * Register an attribute to interact with.
	 *
	 * @param class-string $attribute Attribute class to interact.
	 * @param callable     $callback  Callback to interact with the attribute.
	 * @phpstan-param callable(\ReflectionAttribute $attribute): mixed $callback Callback to interact with the attribute
	 */
	protected function register_attribute( string $attribute, callable $callback ): void {
		$this->attribute_callbacks[ $attribute ] = $callback;
	}
}
