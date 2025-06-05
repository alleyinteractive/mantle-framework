<?php
/**
 * Database_Table_Model class file
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use InvalidArgumentException;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Query\Database_Query_Builder;
use Mantle\Support\Str;

use function Mantle\Support\Helpers\class_basename;

/**
 * Database Table Model
 *
 * A model to represent items in a database table. Ideally models in Mantle will
 * represent posts/terms/users/etc. but there may be cases where you need
 * to represent a custom table in the database. This class provides a base for
 * such models, allowing you to define the table name and handle basic CRUD
 * operations.
 */
abstract class Database_Table_Model extends Model implements Updatable {
	/**
	 * The table name for the model.
	 */
	public static function get_table_name(): string {
		return Str::snake( class_basename( static::class ), '_' );
	}

	/**
	 * Get the primary key for the model.
	 */
	public static function get_query_builder_class(): ?string {
		return Database_Query_Builder::class;
	}

	/**
	 * Find a queue record by ID.
	 *
	 * @throws InvalidArgumentException If the provided object is not a string.
	 *
	 * @param mixed $object The object to find.
	 */
	public static function find( mixed $object ): ?static {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$primary_key = static::$primary_key;

		$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . static::get_table_name() . " WHERE {$primary_key} = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL
				$object,
			),
			ARRAY_A,
		);

		return $result ? static::new_from_existing( $result ) : null;
	}

	/**
	 * Save the model.
	 *
	 * @throws Model_Exception If the model is not saved.
	 *
	 * @param array<mixed> $attributes Attributes to save.
	 */
	public function save( array $attributes = [] ): bool {
		$this->set_attributes( $attributes );

		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		if ( $this->exists ) {
			$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prefix . static::get_table_name(),
				$this->get_attributes(),
				[ static::$primary_key => $this->get_attribute( static::$primary_key ) ],
			);

			if ( ! $result ) {
				throw new Model_Exception(
					sprintf(
						'Failed to update %s table. Please check your database connection and permissions.',
						static::get_table_name(),
					),
				);
			}
		} else {
			$result = $wpdb->insert( $wpdb->prefix . static::get_table_name(), $this->get_attributes() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( ! $result ) {
				throw new Model_Exception(
					sprintf(
						'Failed to insert into %s table. Please check your database connection and permissions.',
						static::get_table_name(),
					),
				);
			}

			$this->set_attribute( static::$primary_key, $wpdb->insert_id );

			$this->exists = true;
		}

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @throws Model_Exception If the model is not deleted.
	 *
	 * @param bool $force Force delete the mode.
	 */
	public function delete( bool $force = false ): mixed {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		if ( ! $this->exists ) {
			throw new Model_Exception( 'Cannot delete a model that does not exist.' );
		}

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . static::get_table_name(),
			[ static::$primary_key => $this->get_attribute( static::$primary_key ) ],
		);

		if ( ! $result ) {
			throw new Model_Exception(
				sprintf(
					'Failed to delete from %s table. Please check your database connection and permissions.',
					static::get_table_name(),
				),
			);
		}

		$this->exists = false;

		return true;
	}
}
