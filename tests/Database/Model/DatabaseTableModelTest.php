<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Database_Table_Model;
use Mantle\Database\Model\Model_Exception;
use Mantle\Testing\FrameworkTestCase;
use ValueError;

/**
 * Database Model tests
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
				name VARCHAR(255) NULL,
				address VARCHAR(255) NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				example_enum VARCHAR(255) NULL,
				boolean_value BOOLEAN NULL,
				json_data LONGTEXT NULL,
				float_value VARCHAR(255) NULL,
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

	public function test_array_casting(): void {
		$item = new TestableDatabaseModel( [
			'name' => 'Test Item',
			'json_data' => [ 'key' => 'value' ],
		] );

		$this->assertIsArray( $item->json_data );
		$this->assertEquals( [ 'key' => 'value' ], $item->json_data );

		$item->save();

		$this->assertEquals( [ 'key' => 'value' ], $item->json_data );

		$item->refresh();

		$this->assertEquals( [ 'key' => 'value' ], $item->json_data );

		$this->assertInstanceOf( TestableDatabaseModel::class, $item );
		$this->assertDatabaseHas( TestableDatabaseModel::get_table_name(), [
			'id' => $item->id,
			'json_data' => json_encode( [ 'key' => 'value' ] ),
		] );
	}

	public function test_boolean_casting(): void {
		$item = new TestableDatabaseModel( [
			'name' => 'Test Item',
			'boolean_value' => true,
		] );

		$this->assertTrue( $item->boolean_value );

		$item->save();

		$this->assertInstanceOf( TestableDatabaseModel::class, $item );
		$this->assertDatabaseHas( TestableDatabaseModel::get_table_name(), [
			'id' => $item->id,
			'boolean_value' => 1,
		] );
	}

	public function test_float_casting(): void {
		$item = new TestableDatabaseModel( [
			'name' => 'Test Item',
			'float_value' => 123.456,
		] );

		$this->assertIsFloat( $item->float_value );
		$this->assertEquals( 123.456, $item->float_value );

		$item->save();

		$this->assertInstanceOf( TestableDatabaseModel::class, $item );
		$this->assertDatabaseHas( TestableDatabaseModel::get_table_name(), [
			'id' => $item->id,
			'float_value' => '123.456',
		] );
	}

	public function test_enum_attribute(): void {
		$item = new TestableDatabaseModelWithEnum( [
			'name' => 'Test Item',
			'example_enum' => ExampleEnum::VALUE_TWO,
		] );

		$this->assertEquals( ExampleEnum::VALUE_TWO, $item->example_enum );

		$item->save();

		$this->assertInstanceOf( TestableDatabaseModelWithEnum::class, $item );
		$this->assertDatabaseHas( TestableDatabaseModelWithEnum::get_table_name(), [
			'id' => $item->id,
			'example_enum' => ExampleEnum::VALUE_TWO->value,
		] );
		$this->assertDatabaseHas( TestableDatabaseModelWithEnum::get_table_name(), [
			'id' => $item->id,
			'example_enum' => ExampleEnum::VALUE_TWO,
		] );
		$this->assertEquals( ExampleEnum::VALUE_TWO, $item->example_enum );

		$item->example_enum = ExampleEnum::VALUE_THREE;
		$item->save();

		$this->assertDatabaseHas( TestableDatabaseModelWithEnum::get_table_name(), [
			'id' => $item->id,
			'example_enum' => ExampleEnum::VALUE_THREE->value,
		] );
		$this->assertEquals( ExampleEnum::VALUE_THREE, $item->example_enum );
	}

	public function test_enum_attribute_as_string(): void {
		$item = new TestableDatabaseModelWithEnum( [
			'name' => 'Test Item',
			'example_enum' => 'value_one',
		] );

		$this->assertEquals( ExampleEnum::VALUE_ONE, $item->example_enum );

		$item->save();

		$this->assertInstanceOf( TestableDatabaseModelWithEnum::class, $item );
		$this->assertDatabaseHas( TestableDatabaseModelWithEnum::get_table_name(), [
			'id' => $item->id,
			'example_enum' => ExampleEnum::VALUE_ONE->value,
		] );
	}

	public function test_enum_attribute_invalid(): void {
		$this->expectException( ValueError::class );

		new TestableDatabaseModelWithEnum( [
			'name' => 'Test Item',
			'example_enum' => 'invalid_value',
		] );
	}
}

class TestableDatabaseModel extends Database_Table_Model {
	protected array $casts = [
		'json_data' => 'array',
		'boolean_value' => 'boolean',
		'float_value' => 'float',
	];
}

enum ExampleEnum: string {
	case VALUE_ONE = 'value_one';
	case VALUE_TWO = 'value_two';
	case VALUE_THREE = 'value_three';
}

class TestableDatabaseModelWithEnum extends Database_Table_Model {
	protected array $casts = [
		'example_enum' => ExampleEnum::class,
	];

	protected function get_enum_attributes(): array {
		return [
			'example_enum' => ExampleEnum::class,
		];
	}

	public static function get_table_name(): string {
		return TestableDatabaseModel::get_table_name();
	}
}
