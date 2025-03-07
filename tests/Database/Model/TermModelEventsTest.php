<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Term;
use Mantle\Testing\Framework_Test_Case;

/**
 * Test non-WordPress specific logic of the model
 */
class TermModelEventsTest extends Framework_Test_Case {
	public function setUp(): void {
		parent::setUp();

		Testable_Term_Model_Event::flush_event_listeners();
		Testable_Term_Model_Event::boot_term_events();
	}

	public function tearDown(): void {
		parent::tearDown();

		unregister_taxonomy( 'test-taxonomy' );
	}

	public function test_closure_event() {
		$_SERVER['__test_creating_event_fired'] = false;
		$_SERVER['__test_created_event_fired'] = false;

		$_SERVER['__test_updating_event_fired'] = false;
		$_SERVER['__test_updated_event_fired'] = false;

		$_SERVER['__test_deleting_event_fired'] = false;
		$_SERVER['__test_deleted_event_fired'] = false;

		// Testable_Term_Model_Event::creating( function() { $_SERVER['__test_creating_event_fired'] = microtime( true ); } );
		Testable_Term_Model_Event::created( function() { $_SERVER['__test_created_event_fired'] = microtime( true ); } );
		Testable_Term_Model_Event::updating( function() { $_SERVER['__test_updating_event_fired'] = microtime( true ); } );
		Testable_Term_Model_Event::updated( function() { $_SERVER['__test_updated_event_fired'] = microtime( true ); } );
		Testable_Term_Model_Event::deleting( function() { $_SERVER['__test_deleting_event_fired'] = microtime( true ); } );
		Testable_Term_Model_Event::deleted( function() { $_SERVER['__test_deleted_event_fired'] = microtime( true ); } );

		$model = new Testable_Term_Model_Event(
			[
				'name'   => 'Example Title',
			]
		);

		$model->save();

		// Update the model.
		$model->save(
			[
				'name' => 'A Updated Title',
			]
		);

		$model->delete();

		// $this->assertNotEmpty( $_SERVER['__test_creating_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_created_event_fired'] );

		$this->assertNotEmpty( $_SERVER['__test_updating_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_updated_event_fired'] );

		$this->assertNotEmpty( $_SERVER['__test_deleting_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_deleted_event_fired'] );

		$this->assertTrue( $_SERVER['__test_created_event_fired'] < $_SERVER['__test_updated_event_fired'] );
		$this->assertTrue( $_SERVER['__test_updated_event_fired'] < $_SERVER['__test_deleted_event_fired'] );
	}

	/**
	 * Ensure that the model events are fired even if not interfacing with a model directly.
	 */
	public function test_non_model_event() {
		$_SERVER['__test_non_model_created_event_fired'] = false;
		$_SERVER['__test_non_model_updated_event_fired'] = false;
		$_SERVER['__test_non_model_deleted_event_fired'] = false;

		Testable_Term_Model_Event::created( function() { $_SERVER['__test_non_model_created_event_fired'] = microtime( true ); } );
		Testable_Term_Model_Event::updated( function() { $_SERVER['__test_non_model_updated_event_fired'] = microtime( true ); } );
		Testable_Term_Model_Event::deleted( function() { $_SERVER['__test_non_model_deleted_event_fired'] = microtime( true ); } );

		$insert  = \wp_insert_term( 'Inserted Term', 'category' );
		$term_id = $insert['term_id'];

		\wp_update_term(
			$term_id,
			'category',
			[
				'name' => 'Updated Title',
			]
		);

		wp_delete_term( $term_id, 'category' );

		$this->assertNotEmpty( $_SERVER['__test_non_model_created_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_non_model_updated_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_non_model_deleted_event_fired'] );

		$this->assertTrue( $_SERVER['__test_non_model_created_event_fired'] < $_SERVER['__test_non_model_updated_event_fired'] );
		$this->assertTrue( $_SERVER['__test_non_model_updated_event_fired'] < $_SERVER['__test_non_model_deleted_event_fired'] );
	}

	public function test_model_event_via_for() {
		$_SERVER['__test_model_event_via_for_created_event_fired'] = false;
		$_SERVER['__test_model_event_via_for_updated_event_fired'] = false;
		$_SERVER['__test_model_event_via_for_deleted_event_fired'] = false;

		register_taxonomy( 'test-taxonomy', 'post' );

		$model = Term::for( 'test-taxonomy' );

		$model->created( fn () => $_SERVER['__test_model_event_via_for_created_event_fired'] = microtime( true ) );
		$model->updated( fn () => $_SERVER['__test_model_event_via_for_updated_event_fired'] = microtime( true ) );
		$model->deleted( fn () => $_SERVER['__test_model_event_via_for_deleted_event_fired'] = microtime( true ) );

		$insert  = \wp_insert_term( 'Inserted Term', 'test-taxonomy' );
		$term_id = $insert['term_id'];

		\wp_update_term(
			$term_id,
			'test-taxonomy',
			[
				'name' => 'Updated Title',
			]
		);

		wp_delete_term( $term_id, 'test-taxonomy' );

		$this->assertNotEmpty( $_SERVER['__test_model_event_via_for_created_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_model_event_via_for_updated_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_model_event_via_for_deleted_event_fired'] );
	}
}

class Testable_Term_Model_Event extends Term {
	public static $object_name = 'category';
}
