<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Registration\Register_Taxonomy;
use Mantle\Database\Model\Term;
use Mantle\Testing\Framework_Test_Case;


class Test_Term_Object extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();
		Example_Taxonomy::register_object();
	}

	protected function tearDown(): void {
		parent::tearDown();
		unregister_taxonomy( Example_Taxonomy::get_object_name() );
	}

	public function test_term_object() {
		$term   = $this->factory->term->create_and_get();
		$object = Testable_Tag::find( $term );

		$this->assertEquals( $object->id(), $term->term_id );
		$this->assertEquals( $object->name(), $term->name );
		$this->assertEquals( $object->slug(), $term->slug );
		$this->assertEquals( $object->description(), $term->description );
		$this->assertNull( $object->status );

		// Retrieve an attribute straight from the object itself.
		$this->assertEquals( $term->taxonomy, $object->get( 'taxonomy' ) );

		// Test that you can get the WordPress object.
		$core_object = $object->core_object();
		$this->assertInstanceOf( \WP_Term::class, $core_object );
		$this->assertEquals( $object->id(), $core_object->term_id );
	}

	public function test_term_object_parent() {
		$parent_id = $this->factory->term->create( [ 'taxonomy' => 'category' ] );
		$term_id   = $this->factory->term->create( [ 'taxonomy' => 'category', 'parent' => $parent_id ] );

		$object = Testable_Category::find( $term_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Testable_Category::class, $parent_object );
		$this->assertEquals( $parent_object->id(), $parent_id );
	}

	public function test_term_meta() {
		$term   = $this->factory->term->create_and_get( [ 'taxonomy' => 'category' ] );
		$object = Testable_Category::find( $term );

		$this->assertEmpty( \get_term_meta( $term->term_id, 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertEquals( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertEquals( 'meta_value_to_set', \get_term_meta( $term->term_id, 'meta_key_to_set', true ) );
	}

	public function test_updating_term() {
		$term   = $this->factory->term->create_and_get();
		$object = Testable_Tag::find( $term );

		$object->name = 'Updated Content';
		$object->save();

		$this->assertEquals( 'Updated Content', $object->name );

		$term = \get_term( $term->term_id, $term->taxonomy );
		$this->assertEquals( 'Updated Content', $term->name );
	}

	public function test_setting_id() {
		$this->expectException( Model_Exception::class );

		$term   = $this->factory->term->create_and_get();
		$object = Testable_Tag::find( $term );

		$object->term_id = 12345;
		$object->save();
	}

	public function test_deleting_term() {
		$term   = $this->factory->term->create_and_get();
		$object = Testable_Tag::find( $term );
		$object->delete();

		$this->assertEmpty( get_term( $term->term_id, $term->taxonomy ) );
	}

	public function test_saving_term_through_model() {
		$term = new Term();
		$term->name = 'test-term';
		$term->taxonomy = 'category';
		$term->save();

		$this->assertNotEmpty( $term->id() );
	}

	public function test_model_incorrect_taxonomy() {
		$tag = $this->factory->term->create( [ 'taxonomy' => 'post_tag' ] );

		$this->assertInstanceOf( Testable_Tag::class, Testable_Tag::find( $tag ) );
		$this->assertNull( Testable_Category::find( $tag ) );
	}

	public function test_model_inferred_taxonomy() {
		$term = $this->factory->term->create( [ 'taxonomy' => Example_Taxonomy::get_object_name() ] );
		$this->assertInstanceOf( Example_Taxonomy::class, Example_Taxonomy::find( $term ) );
		$this->assertNull( Testable_Category::find( $term ) );
	}

	public function test_model_taxonomy_assumed() {
		$object = new Example_Taxonomy( [ 'name' => 'assumed term' ] );
		$object->save();
		$this->assertNotEmpty( $object->id() );
		$this->assertEquals( Example_Taxonomy::get_object_name(), $object->get( 'taxonomy' ) );
	}
}

class Testable_Category extends Term {
	public static $object_name = 'category';
}

class Testable_Tag extends Term {
	public static $object_name = 'post_tag';
}

class Example_Taxonomy extends Term implements Registrable {
	use Register_Taxonomy;

	public static function get_registration_args(): array {
		return [ 'public' => true ];
	}
}
