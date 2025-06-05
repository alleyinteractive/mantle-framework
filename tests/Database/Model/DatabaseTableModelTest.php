<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Database_Table_Model;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\TestCase;

/**
 * Test non-WordPress specific logic of the model
 */
class DatabaseTableModelTest extends FrameworkTestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$wpdb->testable_table = 'mantle_testable_table';

		// Delete the table if it exists to ensure a clean state for tests.
		$wpdb->query(
			"DROP TABLE IF EXISTS {$wpdb->prefix}{$wpdb->testable_table}",
		);

		$wpdb->query(
			"CREATE TABLE {$wpdb->prefix}{$wpdb->testable_table} (
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

class TestableDatabaseModel extends Database_Table_Model {
	public static function get_table_name(): string {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		return $wpdb->testable_table;
	}
}
