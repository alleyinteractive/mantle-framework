<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Database_Table_Model;
use Mantle\Testing\FrameworkTestCase;

/**
 * Database_Table_Model tests.
 */
class DatabaseTableModelTest extends FrameworkTestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$table_name = TestableDatabaseModel::get_table_name();

		// Delete the table if it exists to ensure a clean state for tests.
		$wpdb->query(
			"DROP TABLE IF EXISTS {$wpdb->prefix}{$table_name}",
		);

		$wpdb->query(
			"CREATE TABLE {$wpdb->prefix}{$table_name} (
				id bigint unsigned NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				address VARCHAR(255) NOT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) {$wpdb->get_charset_collate()}
			",
		);
	}

	public function test_create_item(): void {
		$item = TestableDatabaseModel::create( [
			'name' => 'John Doe',
		] );

		$this->assertInstanceOf( TestableDatabaseModel::class, $item );
		$this->assertNotNull( $item->id );
		$this->assertGreaterThan( 0, $item->id );

		$this->assertDatabaseHas(
			TestableDatabaseModel::get_table_name(),
			[
				'name' => 'John Doe',
			],
		);
	}

	public function test_update_item(): void {
		$item = TestableDatabaseModel::create( [
			'name' => 'John Doe',
		] );

		$item->name = 'Jane Doe';
		$item->save();

		$this->assertDatabaseHas(
			TestableDatabaseModel::get_table_name(),
			[
				'id' => $item->id,
				'name' => 'Jane Doe',
			],
		);
	}

	public function test_delete_item(): void {
		$item = TestableDatabaseModel::create( [
			'name' => 'John Doe',
		] );

		$item->delete();

		$this->assertDatabaseDoesNotHave(
			TestableDatabaseModel::get_table_name(),
			[
				'name' => 'John Doe',
			],
		);
	}
}

class TestableDatabaseModel extends Database_Table_Model {}
