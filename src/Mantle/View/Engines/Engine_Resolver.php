<?php
/**
 * Engine_Resolver class file.
 *
 * @package Mantle
 */

namespace Mantle\View\Engines;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\View\Engine;

/**
 * View Engine Resolver
 */
class Engine_Resolver {
	/**
	 * The array of engine resolvers.
	 *
	 * @var array<string, \Closure(): Engine>
	 */
	protected array $resolvers = [];

	/**
	 * The resolved engine instances.
	 *
	 * @var array<string, Engine>
	 */
	protected array $resolved = [];

	/**
	 * Register a new engine resolver.
	 *
	 * The engine string typically corresponds to a file extension.
	 *
	 * @param  string             $engine
	 * @param  \Closure(): Engine $resolver
	 */
	public function register( string $engine, Closure $resolver ): void {
		unset( $this->resolved[ $engine ] );

		$this->resolvers[ $engine ] = $resolver;
	}

	/**
	 * Resolve an engine instance by name.
	 *
	 * @param string $engine
	 *
	 * @throws InvalidArgumentException Thrown on unknown engine.
	 */
	public function resolve( string $engine ): Engine {
		if ( isset( $this->resolved[ $engine ] ) ) {
			return $this->resolved[ $engine ];
		}

		if ( isset( $this->resolvers[ $engine ] ) ) {
			$this->resolved[ $engine ] = $this->resolvers[ $engine ]();
			return $this->resolved[ $engine ];
		}

		throw new InvalidArgumentException( "Engine [{$engine}] not found." );
	}
}
