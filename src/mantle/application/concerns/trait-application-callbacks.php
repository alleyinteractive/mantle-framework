<?php
/**
 * Application_Callbacks trait file
 *
 * @package Mantle
 */
namespace Mantle\Application\Concerns;

/**
 * Application Callbacks
 *
 * @mixin \Mantle\Application\Application
 */
trait Application_Callbacks {
	/**
	 * The array of booting callbacks.
	 *
	 * @var callable[]
	 */
	protected array $booting_callbacks = [];

	/**
	 * The array of booted callbacks.
	 *
	 * @var callable[]
	 */
	protected array $booted_callbacks = [];

	/**
	 * All of the registered service providers.
	 *
	 * @var callable[]
	 */
	protected array $terminating_callbacks = [];

	/**
	 * Register a new boot listener.
	 *
	 * @param callable(\Mantle\Contracts\Application $app): void $callback Callback for the listener.
	 */
	public function booting( callable $callback ): static {
		$this->booting_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param callable(\Mantle\Contracts\Application $app): void $callback Callback for the listener.
	 */
	public function booted( callable $callback ): static {
		$this->booted_callbacks[] = $callback;

		if ( $this->is_booted() ) {
			$this->fire_app_callbacks( [ $callback ] );
		}

		return $this;
	}

	/**
	 * Register a new terminating callback.
	 *
	 * @param callable(\Mantle\Contracts\Application $app): void $callback Callback for the listener.
	 */
	public function terminating( callable $callback ): static {
		$this->terminating_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Flush the application's callbacks.
	 */
	protected function flush_callbacks(): void {
		$this->booting_callbacks = [];
		$this->booted_callbacks = [];
		$this->terminating_callbacks = [];
	}

	/**
	 * Call the booting callbacks for the application.
	 *
	 * @param callable[] $callbacks Callbacks to fire.
	 */
	protected function fire_app_callbacks( array $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$callback( $this );
		}
	}
}
