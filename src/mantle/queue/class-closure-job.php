<?php
/**
 * Closure_Job class file
 *
 * phpcs:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Closure;
use DateTimeInterface;
use Laravel\SerializableClosure\SerializableClosure;
use Mantle\Contracts\Queue\Can_Queue;
use ReflectionFunction;
use Throwable;

/**
 * Closure Job
 *
 * Storage of the closure-based queue job.
 */
class Closure_Job implements Can_Queue {
	/**
	 * The delay before the job will be run.
	 */
	public int|DateTimeInterface $delay;

	/**
	 * The callbacks that should be run on failure.
	 */
	public array $failure_callbacks = [];

	/**
	 * Create a new job instance.
	 *
	 * @param Closure $closure Closure to wrap.
	 */
	public static function create( Closure $closure ): Closure_Job {
		$reflection_closure = new \ReflectionFunction( $closure );

		// Check if the closure is bound to WP_CLI\Runner. If so, unbind it because
		// this will cause a serialization error. Without this, we cannot dispatch
		// to the queue from WP-CLI.
		if ( \WP_CLI\Runner::class === $reflection_closure->getClosureScopeClass()?->getName() ) {
			$closure = $closure->bindTo( null, null );
		}

		return new self( new SerializableClosure( $closure ) );
	}

	/**
	 * Constructor.
	 *
	 * @param SerializableClosure $closure Serialized closure to wrap.
	 */
	public function __construct( public SerializableClosure $closure ) {
	}

	/**
	 * Handle the queue job.
	 */
	public function handle(): void {
		$callback = $this->closure->getClosure();

		$callback();
	}

	/**
	 * Set the delay before the job will be run.
	 *
	 * @param DateTimeInterface|int $delay Delay in seconds or DateTime instance.
	 * @return static
	 */
	public function delay( DateTimeInterface|int $delay ) {
		$this->delay = $delay;

		return $this;
	}

	/**
	 * Add a callback to be executed if the job fails.
	 *
	 * @param mixed $callback
	 * @return static
	 */
	public function on_failure( $callback ) {
		$this->failure_callbacks[] = $callback instanceof Closure
			? new SerializableClosure( $callback )
			: $callback;

		return $this;
	}

	/**
	 * Handle a job failure.
	 *
	 * @param \Throwable $e Exception.
	 */
	public function failed( Throwable $e ): void {
		foreach ( $this->failure_callbacks as $failure_callback ) {
			$failure_callback( $e );
		}
	}

	/**
	 * Get the queue job ID.
	 *
	 * @return mixed
	 */
	public function get_id() {
		$closure = $this->closure->getClosure();

		if ( ! $closure ) { // @phpstan-ignore-line negated
			return 'Invalid Closure Job';
		}

		$reflection = new ReflectionFunction( $closure );

		return 'Closure (' . basename( $reflection->getFileName() ) . ':' . $reflection->getStartLine() . ')';
	}
}
