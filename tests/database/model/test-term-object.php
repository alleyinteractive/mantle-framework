<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Database\Model\Model_Exception;
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

	public function test_updating_term() {
		$term   = $this->factory->term->create_and_get();
		$object = Term::find( $term );

		$object->name = 'Updated Content';
		$object->save();

		$this->assertEquals( 'Updated Content', $object->name );

		$term = \get_term( $term->term_id, $term->taxonomy );
		$this->assertEquals( 'Updated Content', $term->name );
	}

	public function test_setting_id() {
		$this->expectException( Model_Exception::class );

		$term   = $this->factory->term->create_and_get();
		$object = Term::find( $term );

		$object->term_id = 12345;
		$object->save();
	}

	public function test_deleting_term() {
		$term   = $this->factory->term->create_and_get();
		$object = Term::find( $term );
		$object->delete();

		$this->assertEmpty( get_term( $term->term_id, $term->taxonomy ) );
	}
}
