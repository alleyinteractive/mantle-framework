<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Database\Model\Term;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Term_Object extends WP_UnitTestCase {
	public function test_term_object() {
		$term   = $this->factory->term->create_and_get();
		$object = Term::find( $term );

		$this->assertEquals( $object->id(), $term->term_id );
		$this->assertEquals( $object->name(), $term->name );
		$this->assertEquals( $object->slug(), $term->slug );
		$this->assertEquals( $object->description(), $term->description );
		$this->assertNull( $object->status );

		// Retrieve an attribute straight from the object itself.
		$this->assertEquals( $term->taxonomy, $object->get( 'taxonomy' ) );
	}

	public function test_term_object_parent() {
		$parent_id = $this->factory->term->create();
		$term_id   = $this->factory->term->create( [ 'parent' => $parent_id ] );

		$object = Term::find( $term_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Term::class, $parent_object );
		$this->assertEquals( $parent_object->id(), $parent_id );
	}

	public function test_term_meta() {
		$term   = $this->factory->term->create_and_get( [ 'taxonomy' => 'category' ] );
		$object = Term::find( $term );

		$this->assertEmpty( \get_term_meta( $term->term_id, 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertEquals( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertEquals( 'meta_value_to_set', \get_term_meta( $term->term_id, 'meta_key_to_set', true ) );
	}
}
