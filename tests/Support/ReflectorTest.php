<?php
namespace Mantle\Tests\Support;

use Attribute;
use Mantle\Support\Reflector;
use PHPUnit\Framework\TestCase;

class ReflectorTest extends TestCase {
	public function test_it_can_read_a_class_attribute(): void {
		$attributes = Reflector::get_attributes_for_method(
			ExampleClass::class,
			'example_base_method'
		);

		$this->assertCount( 1, $attributes );
		$this->assertEquals( ExampleAttribute::class, $attributes[0]->getName() );
	}

	public function test_it_can_read_a_child_class_attribute(): void {
		$attributes = Reflector::get_attributes_for_method(
			ExampleChildClass::class,
			'example_base_method'
		);

		$this->assertCount( 1, $attributes );
		$this->assertEquals( ExampleAttribute::class, $attributes[0]->getName() );
	}
}

#[Attribute]
class ExampleAttribute {}

#[ExampleAttribute]
class ExampleClass {
	public function example_base_method(): void {}
}

class ExampleChildClass extends ExampleClass {}

class ExampleClassWithMethod {
	#[ExampleAttribute]
	public function example_method() {}
}
