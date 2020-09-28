<?php
/**
 * Engine_Resolver class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\View\Engines;

use Closure;
use InvalidArgumentException;
use Mantle\Framework\Contracts\View\Engine;

/**
 * View Engine Resolver
 */
class Engine_Resolver {
	/**
	 * The array of engine resolvers.
	 *
	 * @var array
	 */
	protected $resolvers = [];

	/**
	 * The resolved engine instances.
	 *
	 * @var array
	 */
	protected $resolved = [];

	/**
	 * Register a new engine resolver.
	 *
	 * The engine string typically corresponds to a file extension.
	 *
	 * @param  string   $engine
	 * @param  \Closure $resolver
	 * @return void
	 */
	public function register( $engine, Closure $resolver ) {
			unset( $this->resolved[ $engine ] );

			$this->resolvers[ $engine ] = $resolver;
	}

	/**
	 * Resolve an engine instance by name.
	 *
	 * @param  string $engine
	 * @return Engine
	 *
	 * @throws \InvalidArgumentException
	 */
	public function resolve( $engine ): Engine {
		if ( isset( $this->resolved[ $engine ] ) ) {
			return $this->resolved[ $engine ];
		}

		if ( isset( $this->resolvers[ $engine ] ) ) {
			return $this->resolved[ $engine ] = call_user_func( $this->resolvers[ $engine ] );
		}

		throw new InvalidArgumentException( "Engine [{$engine}] not found." );
	}
}
