<?php
/**
 * Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\View;

use Mantle\Framework\Contracts\Container;
use Mantle\Framework\Support\Arr;

/**
 * View Factory
 */
class Factory {
	/**
	 * The IoC container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = [];

	/**
	 * Constructor.
	 *
	 * @param Container $container Container to set.
	 */
	public function __construct( Container $container ) {
		$this->set_container( $container );
		$this->share( '__env', $this );
	}

	/**
	 * Set the container to use.
	 *
	 * @param Container $container Container instance.
	 */
	public function set_container( Container $container ) {
		$this->container = $container;
		return $this;
	}

	/**
	 * Get the container to use.
	 *
	 * @return Container
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param array|string $key Key to share.
	 * @param mixed|null   $value Value to share.
	 * @return mixed
	 */
	public function share( $key, $value = null ) {
		$keys = is_array( $key ) ? $key : [ $key => $value ];

		foreach ( $keys as $key => $value ) {
			$this->shared[ $key ] = $value;
		}

		return $value;
	}

	/**
	 * Get an item from the shared data.
	 *
	 * @param string $key Key to get item by.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function shared( $key, $default = null ) {
		return Arr::get( $this->shared, $key, $default );
	}

	/**
	 * Get all of the shared data for the environment.
	 *
	 * @return array
	 */
	public function get_shared(): array {
		return $this->shared;
	}
}
