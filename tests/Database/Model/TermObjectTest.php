<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Contracts\Database\Registrable;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Registration\Register_Taxonomy;
use Mantle\Database\Model\Term;
use Mantle\Testing\FrameworkTestCase;

class TermObjectTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();
		Example_Taxonomy::register_object();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$taxonomy = Example_Taxonomy::get_object_name();

		$this->assertNotNull( $taxonomy );

		unregister_taxonomy( $taxonomy );
	}

	public function test_term_object(): void {
		$term   = static::factory()->term->create_and_get();
		$object = Testable_Tag::find_or_fail( $term );

		$this->assertSame( $object->id(), $term->term_id );
		$this->assertSame( $object->name(), $term->name );
		$this->assertSame( $object->slug(), $term->slug );
		$this->assertSame( $object->description(), $term->description );
		$this->assertNull( $object->get( 'status' ) );

		// Retrieve an attribute straight from the object itself.
		$this->assertSame( $term->taxonomy, $object->get( 'taxonomy' ) );

		// Test that you can get the WordPress object.
		$core_object = $object->core_object();
		$this->assertInstanceOf( \WP_Term::class, $core_object );
		$this->assertSame( $object->id(), $core_object->term_id );
	}

	public function test_term_object_parent(): void {
		$parent_id = static::factory()->term->create( [ 'taxonomy' => 'category' ] );
		$term_id   = static::factory()->term->create( [ 'taxonomy' => 'category', 'parent' => $parent_id ] );

		$this->assertIsInt( $term_id );

		$object = Testable_Category::find_or_fail( $term_id );

		$parent_object = $object->parent();

		$this->assertInstanceOf( Testable_Category::class, $parent_object );
		$this->assertSame( $parent_object->id(), $parent_id );
	}

	public function test_term_meta(): void {
		$term   = static::factory()->term->create_and_get( [ 'taxonomy' => 'category' ] );
		$object = Testable_Category::find_or_fail( $term );

		$this->assertEmpty( \get_term_meta( $term->term_id, 'meta_value_to_set', true ) );

		$object->set_meta( 'meta_key_to_set', 'meta_value_to_set' );
		$this->assertSame( 'meta_value_to_set', $object->get_meta( 'meta_key_to_set' ) );
		$this->assertSame( 'meta_value_to_set', \get_term_meta( $term->term_id, 'meta_key_to_set', true ) );
	}

	public function test_updating_term(): void {
		$term   = static::factory()->term->create_and_get();
		$object = Testable_Tag::find_or_fail( $term );

		$object->name = 'Updated Content';
		$object->save();

		$this->assertSame( 'Updated Content', $object->name );

		$term = \get_term( $term->term_id, $term->taxonomy );

		$this->assertInstanceOf( \WP_Term::class, $term );
		$this->assertSame( 'Updated Content', $term->name );
	}

	public function test_setting_id(): void {
		$this->expectException( Model_Exception::class );

		$term   = static::factory()->term->create_and_get();
		$object = Testable_Tag::find_or_fail( $term );

		$object->term_id = 12345;
		$object->save();
	}

	public function test_deleting_term(): void {
		$term   = static::factory()->term->create_and_get();
		$object = Testable_Tag::find_or_fail( $term );
		$object->delete();

		$this->assertEmpty( get_term( $term->term_id, $term->taxonomy ) );
	}

	public function test_saving_term_through_model(): void {
		$term = new Term();
		$term->name = 'test-term';
		$term->taxonomy = 'category';
		$term->save();

		$this->assertNotEmpty( $term->id() );
		$this->assertTrue( $term->exists );
		$this->assertSame( 'test-term', $term->slug() );
		$this->assertNotEmpty( $term->get( 'term_taxonomy_id' ) );
	}

	public function test_unsaved_term_parent_attribute(): void {
		$term = new Testable_Tag( [ 'name' => 'Unsaved' ] );

		$this->assertNull( $term->get( 'parent' ) );
		$this->assertNull( $term->parent() );
	}

	public function test_model_incorrect_taxonomy(): void {
		$tag = static::factory()->term->create( [ 'taxonomy' => 'post_tag' ] );

		$this->assertIsInt( $tag );

		$this->assertInstanceOf( Testable_Tag::class, Testable_Tag::find( $tag ) );
		$this->assertNull( Testable_Category::find( $tag ) );
	}

	public function test_model_inferred_taxonomy(): void {
		$term = static::factory()->term->create( [ 'taxonomy' => Example_Taxonomy::get_object_name() ] );

		$this->assertIsInt( $term );
		$this->assertInstanceOf( Example_Taxonomy::class, Example_Taxonomy::find( $term ) );
		$this->assertNull( Testable_Category::find( $term ) );
	}

	public function test_model_taxonomy_assumed(): void {
		$object = new Example_Taxonomy( [ 'name' => 'assumed term' ] );
		$object->save();
		$this->assertNotEmpty( $object->id() );
		$this->assertSame( Example_Taxonomy::get_object_name(), $object->get( 'taxonomy' ) );
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
