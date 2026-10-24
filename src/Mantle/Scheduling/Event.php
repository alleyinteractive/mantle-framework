<?php
/**
 * Event class file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Scheduling;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use DateTimeZone;
use Mantle\Contracts\Application;
use Mantle\Contracts\Container;
use Mantle\Contracts\Exceptions\Handler;
use Mantle\Http_Client\Factory;
use Mantle\Http_Client\Http_Client_Exception;
use Mantle\Support\Traits\Macroable;
use Throwable;

/**
 * Schedule-able Event
 */
class Event {
	use Macroable;
	use Manages_Frequencies;

	/**
	 * The cron expression representing the event's frequency.
	 */
	public string $expression = '* * * * *';

	/**
	 * The list of environments the command should run under.
	 *
	 * @var string[]
	 */
	public array $environments = [];

	/**
	 * The array of filter callbacks.
	 *
	 * @var callable[]
	 */
	protected array $filters = [];

	/**
	 * The array of reject callbacks.
	 *
	 * @var callable[]
	 */
	protected array $rejects = [];

	/**
	 * The array of callbacks to be run before the event is started.
	 *
	 * @var callable[]
	 */
	protected array $before_callbacks = [];

	/**
	 * The array of callbacks to be run after the event is finished.
	 *
	 * @var callable[]
	 */
	protected array $after_callbacks = [];

	/**
	 * The human readable description of the event.
	 */
	public string $description;

	/**
	 * The exit status code of the command.
	 * 0 for success and 1 for failure.
	 */
	public ?int $exit_code = null;

	/**
	 * Exception thrown for the command.
	 */
	public \Throwable $exception;

	/**
	 * Create a new event instance.
	 *
	 * @param callable|string   $callback Event callback or class name.
	 * @param array<mixed>      $parameters Event parameters.
	 * @param DateTimeZone|null $timezone Event timezone.
	 */
	public function __construct(
		protected $callback,
		protected array $parameters = [],
		protected ?DateTimeZone $timezone = null,
	) {
	}

	/**
	 * Run the given event, assumed to be a closure or callable callback.
	 *
	 * @param Application $container
	 */
	public function run( Application $container ): void {
		if ( ! $this->filters_pass( $container ) ) {
			return;
		}

		$this->call_before_callbacks( $container );

		try {
			if ( is_object( $this->callback ) ) {
				$container->call( [ $this->callback, '__invoke' ], $this->parameters );
			} else {
				$container->call( $this->callback, $this->parameters );
			}

			$this->exit_code = 0;
		} catch ( Throwable $e ) {
			$container->make( Handler::class )->report( $e );

			$this->exception = $e;
			$this->exit_code = 1;
		}

		$this->call_after_callbacks( $container );
	}

	/**
	 * Call all of the "before" callbacks for the event.
	 *
	 * @param  \Mantle\Contracts\Container $container
	 */
	public function call_before_callbacks( Container $container ): void {
		foreach ( $this->before_callbacks as $before_callback ) {
			$container->call( $before_callback );
		}
	}

	/**
	 * Call all of the "after" callbacks for the event.
	 *
	 * @param  \Mantle\Contracts\Container $container
	 */
	public function call_after_callbacks( Container $container ): void {
		foreach ( $this->after_callbacks as $after_callback ) {
			$container->call( $after_callback );
		}
	}

	/**
	 * Determine if the given event should run based on the Cron expression.
	 *
	 * @param Application $app
	 */
	public function is_due( Application $app ): bool {
		return $this->expression_passes() &&
			$this->runs_in_environment( $app->environment() );
	}

	/**
	 * Determine if the Cron expression passes.
	 */
	protected function expression_passes(): bool {
		$date = Carbon::now();

		if ( $this->timezone instanceof \DateTimeZone ) {
			$date->setTimezone( $this->timezone );
		}

		return ( new CronExpression( $this->expression ) )->isDue( $date->toDateTimeString() );
	}

	/**
	 * Determine if the event runs in the given environment.
	 *
	 * @param string $environment Environment to check against.
	 */
	public function runs_in_environment( string $environment ): bool {
		return empty( $this->environments ) || in_array( $environment, $this->environments, true );
	}

	/**
	 * Determine if the filters pass for the event.
	 *
	 * @param Application $app Application instance.
	 */
	public function filters_pass( Application $app ): bool {
		foreach ( $this->filters as $filter ) {
			if ( ! $app->call( $filter ) ) {
				return false;
			}
		}

		foreach ( $this->rejects as $reject ) {
			if ( $app->call( $reject ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Register a callback to ping a given URL before the job runs.
	 *
	 * @param  string $url URL to ping.
	 */
	public function ping_before( string $url ): static {
		return $this->before( $this->ping_callback( $url ) );
	}

	/**
	 * Alias to ping_before().
	 *
	 * @deprecated Use ping_before() instead.
	 *
	 * @param  string $url URL to ping.
	 */
	#[\Deprecated( 'Use ping_before() instead.' )]
	public function pingBefore( string $url ): static {
		return $this->ping_before( $url );
	}

	/**
	 * Register a callback to ping a given URL before the job runs if the given condition is true.
	 *
	 * @param  bool   $value Value to compare.
	 * @param  string $url URL to ping.
	 */
	public function ping_before_if( mixed $value, string $url ): static {
		return $value ? $this->ping_before( $url ) : $this;
	}

	/**
	 * Alias to ping_before_if().
	 *
	 * @deprecated Use ping_before_if() instead.
	 *
	 * @param  mixed  $value Value to compare.
	 * @param  string $url URL to ping.
	 */
	#[\Deprecated( 'Use ping_before_if() instead.' )]
	public function pingBeforeIf( mixed $value, string $url ): static {
		return $this->ping_before_if( $value, $url );
	}

	/**
	 * Register a callback to ping a given URL after the job runs.
	 *
	 * @param string $url URL to ping.
	 */
	public function then_ping( string $url ): static {
		return $this->then( $this->ping_callback( $url ) );
	}

	/**
	 * Alias to then_ping().
	 *
	 * @deprecated Use then_ping() instead.
	 *
	 * @param string $url URL to ping.
	 */
	#[\Deprecated( 'Use then_ping() instead.' )]
	public function thenPing( string $url ): static {
		return $this->then_ping( $url );
	}

	/**
	 * Register a callback to ping a given URL after the job runs if the given condition is true.
	 *
	 * @param  bool   $value Value to compare.
	 * @param  string $url URL to ping.
	 */
	public function then_ping_if( mixed $value, string $url ): static {
		return $value ? $this->then_ping( $url ) : $this;
	}

	/**
	 * Alias to then_ping_if().
	 *
	 * @deprecated Use then_ping_if() instead.
	 *
	 * @param  bool   $value Value to compare.
	 * @param  string $url URL to ping.
	 */
	#[\Deprecated( 'Use then_ping_if() instead.' )]
	public function thenPingIf( mixed $value, string $url ): static {
		return $this->then_ping_if( $value, $url );
	}

	/**
	 * Register a callback to ping a given URL if the operation succeeds.
	 *
	 * @param string $url URL to ping.
	 */
	public function ping_on_success( string $url ): static {
		return $this->onSuccess( $this->ping_callback( $url ) );
	}

	/**
	 * Register a callback to ping a given URL if the operation succeeds.
	 *
	 * @param string $url URL to ping.
	 */
	public function pingOnSuccess( string $url ): static {
		return $this->onSuccess( $this->ping_callback( $url ) );
	}

	/**
	 * Register a callback to ping a given URL if the operation fails.
	 *
	 * @param  string $url
	 */
	public function pingOnFailure( string $url ): static {
		return $this->onFailure( $this->ping_callback( $url ) );
	}

	/**
	 * Get the callback that pings the given URL.
	 *
	 * @param string $url URL to ping.
	 * @return \Closure
	 */
	protected function ping_callback( string $url ) {
		return function ( Container $container, Factory $http ) use ( $url ): void {
			try {
				$http->throw_exception()->get( $url );
			} catch ( Http_Client_Exception $e ) {
				$container->make( Handler::class )->report( $e );
			}
		};
	}

	/**
	 * Limit the environments the command should run in.
	 *
	 * @param  string ...$environments Environments to run on.
	 */
	public function environments( string ...$environments ): static {
		$this->environments = $environments;
		return $this;
	}

	/**
	 * Register a callback to further filter the schedule.
	 *
	 * @param \Closure|bool $callback Callback to be invoked.
	 */
	public function when( mixed $callback ): static {
		$this->filters[] = is_callable( $callback ) ? $callback : fn () => $callback;

		return $this;
	}

	/**
	 * Register a callback to further filter the schedule.
	 *
	 * @param \Closure|bool $callback Callback to be invoked.
	 */
	public function skip( mixed $callback ): static {
		$this->rejects[] = is_callable( $callback ) ? $callback : fn () => $callback;

		return $this;
	}

	/**
	 * Register a callback to be called before the operation.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 */
	public function before( Closure $callback ): static {
		$this->before_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Register a callback to be called after the operation.
	 *
	 * @param \Closure $callback  Callback to be invoked.
	 */
	public function after( Closure $callback ): static {
		return $this->then( $callback );
	}

	/**
	 * Register a callback to be called after the operation.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 */
	public function then( Closure $callback ): static {
		$this->after_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Register a callback to be called if the operation succeeds.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 */
	public function on_success( Closure $callback ): static {
		return $this->then(
			function ( Container $container ) use ( $callback ): void {
				if ( 0 === $this->exit_code ) {
					$container->call( $callback, [ $this ] );
				}
			}
		);
	}

	/**
	 * Alias to on_success().
	 *
	 * @deprecated Use on_success() instead.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 */
	#[\Deprecated( 'Use on_success() instead.' )]
	public function onSuccess( Closure $callback ): static {
		return $this->on_success( $callback );
	}

	/**
	 * Register a callback to be called if the operation fails.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 */
	public function on_failure( Closure $callback ): static {
		return $this->then(
			function ( Container $container ) use ( $callback ): void {
				if ( 0 !== $this->exit_code ) {
					$container->call( $callback, [ $this ] );
				}
			}
		);
	}

	/**
	 * Alias to on_failure().
	 *
	 * @deprecated Use on_failure() instead.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 */
	#[\Deprecated( 'Use on_failure() instead.' )]
	public function onFailure( Closure $callback ): static {
		return $this->on_failure( $callback );
	}

	/**
	 * Get the Cron expression for the event.
	 */
	public function get_expression(): string {
		return $this->expression;
	}
}
