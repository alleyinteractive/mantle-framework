<?php
/**
 * Pipeline interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts;

use Closure;

/**
 * Pipeline Contract
 */
interface Pipeline {

	/**
	 * Set the traveler object being sent on the pipeline.
	 *
	 * @param  mixed $traveler
	 */
	public function send( mixed $traveler ): static;

	/**
	 * Set the stops of the pipeline.
	 *
	 * @param  array<callable> $stops
	 */
	public function through( array $stops ): static;

	/**
	 * Set the method to call on the stops.
	 *
	 * @param  string $method
	 */
	public function via( string $method ): static;

	/**
	 * Run the pipeline with a final destination callback.
	 *
	 * @param  \Closure $destination
	 */
	public function then( Closure $destination ): mixed;
}
