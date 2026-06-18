<?php
/**
 * Repository class file.
 *
 * @package Mantle
 */

namespace Mantle\Config;

use ArrayAccess;
use Mantle\Contracts\Config\Repository as Config_Contract;
use Mantle\Support\Arr;
use Mantle\Support\Mixed_Data;

/**
 * Configuration Repository
 *
 * Used to store configuration items for the application.
 *
 * @implements ArrayAccess<string, mixed>
 */
class Repository implements ArrayAccess, Config_Contract {
	/**
	 * Constructor.
	 *
	 * @param array<string, array<mixed>> $items Configuration items for the repository.
	 */
	public function __construct( protected array $items = [] ) {
	}

	/**
	 * Check if a configuration value exists.
	 *
	 * @param string $key Key to get, period-delimited.
	 */
	public function has( string $key ): bool {
		return Arr::has( $this->items, $key );
	}

	/**
	 * Retrieve a configuration value.
	 *
	 * @param string $key Configuration key to get, period-delimited.
	 * @param mixed  $default Default value, optional.
	 */
	public function get( string $key, mixed $default = null ): mixed {
		return Arr::get( $this->items, $key, $default );
	}

	/**
	 * Retrieve a configuration value as Mixed_Data.
	 *
	 * @param string $key Configuration key to get, period-delimited.
	 * @param mixed  $default Default value, optional.
	 */
	public function get_mixed( string $key, mixed $default = null ): Mixed_Data {
		return Mixed_Data::of( $this->get( $key, $default ) );
	}

	/**
	 * Set a configuration value.
	 *
	 * @param array<string, mixed>|string $key Key(s) to set.
	 * @param mixed                       $value Value to set.
	 */
	public function set( array|string $key, mixed $value ): void {
		$keys = is_array( $key ) ? $key : [ $key => $value ];

		foreach ( $keys as $key => $value ) {
			Arr::set( $this->items, $key, $value );
		}
	}

	/**
	 * Get all configuration values.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function all(): array {
		return $this->items;
	}

	/**
	 * Check if a offset exists.
	 *
	 * @param mixed $offset Offset to retrieve.
	 */
	public function offsetExists( mixed $offset ): bool {
		return $this->has( $offset );
	}

	/**
	 * Get an offset.
	 *
	 * @param mixed $offset Offset to retrieve.
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->get( $offset );
	}

	/**
	 * Set an offset.
	 *
	 * @param mixed $offset Offset to set.
	 * @param mixed $value Value to set.
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->set( $offset, $value );
	}

	/**
	 * Unset an offset.
	 *
	 * @param mixed $offset Offset to unset.
	 */
	public function offsetUnset( mixed $offset ): void {
		$this->set( $offset, null );
	}
}
