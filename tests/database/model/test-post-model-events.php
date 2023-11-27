<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Post;
use Mantle\Testing\Framework_Test_Case;

/**
 * Test non-WordPress specific logic of the model
 */
class Test_Post_Model_Events extends Framework_Test_Case {
	public function setUp(): void {
		parent::setUp();

		Testable_Model_Event::flush_event_listeners();
		Testable_Model_Event::boot_post_events();
	}

	public function test_closure_event() {
		$_SERVER['__test_creating_event_fired'] = false;
		$_SERVER['__test_created_event_fired'] = false;

		$_SERVER['__test_updating_event_fired'] = false;
		$_SERVER['__test_updated_event_fired'] = false;

		$_SERVER['__test_trashing_event_fired'] = false;
		$_SERVER['__test_trashed_event_fired'] = false;

		$_SERVER['__test_deleting_event_fired'] = false;
		$_SERVER['__test_deleted_event_fired'] = false;

		// Testable_Model_Event::creating( function() { $_SERVER['__test_creating_event_fired'] = microtime( true ); } );
		Testable_Model_Event::created( function() { $_SERVER['__test_created_event_fired'] = microtime( true ); } );
		Testable_Model_Event::updating( function() { $_SERVER['__test_updating_event_fired'] = microtime( true ); } );
		Testable_Model_Event::updated( function() { $_SERVER['__test_updated_event_fired'] = microtime( true ); } );
		Testable_Model_Event::trashing( function() { $_SERVER['__test_trashing_event_fired'] = microtime( true ); } );
		Testable_Model_Event::trashed( function() { $_SERVER['__test_trashed_event_fired'] = microtime( true ); } );
		Testable_Model_Event::deleting( function() { $_SERVER['__test_deleting_event_fired'] = microtime( true ); } );
		Testable_Model_Event::deleted( function() { $_SERVER['__test_deleted_event_fired'] = microtime( true ); } );

		$model = new Testable_Model_Event(
			[
				'title'   => 'Example Title',
				'content' => 'Example Content',
			]
		);

		$model->save();

		// Update the model.
		$model->save(
			[
				'title' => 'A Updated Title',
			]
		);

		// Trash it and then delete it.
		$model->delete( false );
		$model->delete( true );

		// $this->assertNotEmpty( $_SERVER['__test_creating_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_created_event_fired'] );

		$this->assertNotEmpty( $_SERVER['__test_updating_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_updated_event_fired'] );

		$this->assertNotEmpty( $_SERVER['__test_trashing_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_trashed_event_fired'] );

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

		Testable_Model_Event::created( function() { $_SERVER['__test_non_model_created_event_fired'] = microtime( true ); } );
		Testable_Model_Event::updated( function() { $_SERVER['__test_non_model_updated_event_fired'] = microtime( true ); } );
		Testable_Model_Event::deleted( function() { $_SERVER['__test_non_model_deleted_event_fired'] = microtime( true ); } );

		$post_id = \wp_insert_post(
			[
				'post_type' => 'post',
				'post_title' => 'Inserted By Hand',
			]
		);

		wp_update_post(
			[
				'ID'         => $post_id,
				'post_title' => 'Updated Title',
			]
		);

		wp_delete_post( $post_id, true );

		$this->assertNotEmpty( $_SERVER['__test_non_model_created_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_non_model_updated_event_fired'] );
		$this->assertNotEmpty( $_SERVER['__test_non_model_deleted_event_fired'] );

		$this->assertTrue( $_SERVER['__test_non_model_created_event_fired'] < $_SERVER['__test_non_model_updated_event_fired'] );
		$this->assertTrue( $_SERVER['__test_non_model_updated_event_fired'] < $_SERVER['__test_non_model_deleted_event_fired'] );
	}
}

class Testable_Model_Event extends Post {
	public static $object_name = 'post';
}
