<?php
/**
 * Closure_Job class file
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
 * Abstract Queue Job
 *
 * To be extended by provider-specific queue job classes.
 */
class Closure_Job implements Can_Queue {
	/**
	 * The delay before the job will be run.
	 *
	 * @var int|DateTimeInterface
	 */
	public int|DateTimeInterface $delay;

	/**
	 * The callbacks that should be run on failure.
	 *
	 * @var array
	 */
	public array $failure_callbacks = [];

	/**
	 * Create a new job instance.
	 *
	 * @param Closure $closure Closure to wrap.
	 * @return self
	 */
	public static function create( Closure $closure ): Closure_Job {
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
	public function handle() {
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
	public function failed( Throwable $e ) {
		foreach ( $this->failure_callbacks as $callback ) {
			$callback( $e );
		}
	}

	/**
	 * Get the queue job ID.
	 *
	 * @return mixed
	 */
	public function get_id() {
		$reflection = new ReflectionFunction( $this->closure->getClosure() );

		return 'Closure (' . basename( $reflection->getFileName() ) . ':' . $reflection->getStartLine() . ')';
	}
}
