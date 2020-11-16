<?php
namespace Mantle\Framework\Support;

use Closure;
use InvalidArgumentException;

abstract class Driver_Manager {
	/**
	 * Custom driver creators.
	 *
	 * @var array
	 */
	protected $custom_creators = [];

	/**
	 * Resolve a driver.
	 *
	 * @param string $driver Driver name.
	 * @param mixed ...$args Arguments for the driver.
	 * @return mixed
	 */
	public function resolve_driver( string $driver, ...$args ) {
		if ( isset( $this->custom_creators[ $driver ] ) ) {
			return $this->call_custom_creator( $driver, $args );
		}

		$method = 'create_' . Str::snake( $driver ) . '_driver';
		dd($method);

		if ( method_exists( $this, $method ) ) {
			return $this->$method( ...$args );
		}

		throw new InvalidArgumentException( "Driver [$driver] not supported." );
	}

	/**
	 * Call a custom driver.
	 *
	 * @param string $driver
	 * @param array $args
	 * @return void
	 */
	protected function call_custom_creator( string $driver, array $args ) {
		return $this->custom_creators[ $driver ]( ...$args );
	}

	/**
	 * Extend the manager.
	 *
	 * @param string $name Driver name.
	 * @param Closure $callback Closure to invoke.
	 * @return static
	 */
	public function extend( string $name, Closure $callback ) {
		$this->custom_creators[ $name ] = $callback;
		return $this;
	}
}
