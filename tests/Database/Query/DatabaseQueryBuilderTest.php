<?php
namespace Mantle\Tests\Database\Query;

use Mantle\Database\Model\Database_Table_Model;
use Mantle\Testing\FrameworkTestCase;

class DatabaseQueryBuilderTest extends FrameworkTestCase {
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

	public function test_retrieve_models(): void {
		$this->assertNull( TestableDatabaseModel::first() );

		TestableDatabaseModel::create( [
			'name' => 'John Doe',
		] );

		$this->assertEquals(
			'John Doe',
			TestableDatabaseModel::first()->name,
		);

		$first = TestableDatabaseModel::query()->where( 'name', 'John Doe' )->first();

		$this->assertInstanceOf( TestableDatabaseModel::class, $first );
		$this->assertEquals( 'John Doe', $first->name );
		$this->assertNotNull( $first->id );

		$missing = TestableDatabaseModel::query()->where( 'name', 'Jane Doe' )->first();

		$this->assertNull( $missing );
	}

	public function test_count_models(): void {
		TestableDatabaseModel::create( [
			'name' => 'John Doe',
		] );

		TestableDatabaseModel::create( [
			'name' => 'Jane Doe',
		] );

		$count = TestableDatabaseModel::query()->count();

		$this->assertEquals( 2, $count );

		$this->assertEquals( 0, TestableDatabaseModel::query()->where( 'name', 'Non-existing' )->count() );
	}
}

class TestableDatabaseModel extends Database_Table_Model {}
