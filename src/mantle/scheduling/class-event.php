<?php
/**
 * Event class file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Scheduling;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\TransferException;
use Mantle\Contracts\Application;
use Mantle\Contracts\Container;
use Mantle\Contracts\Exceptions\Handler;
use Mantle\Support\Traits\Macroable;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;

/**
 * Schedulable Event
 */
class Event {
	use Macroable, Manages_Frequencies;

	/**
	 * The event callback.
	 *
	 * @var \Closure|string
	 */
	public $callback;

	/**
	 * The event callback parameters.
	 *
	 * @var array
	 */
	public $parameters;

	/**
	 * The cron expression representing the event's frequency.
	 *
	 * @var string
	 */
	public $expression = '* * * * *';

	/**
	 * The timezone the date should be evaluated on.
	 *
	 * @var \DateTimeZone|string
	 */
	public $timezone;

	/**
	 * The list of environments the command should run under.
	 *
	 * @var array
	 */
	public $environments = [];

	/**
	 * Indicates if the command should not overlap itself.
	 *
	 * @var bool
	 */
	public $without_overlapping = false;

	/**
	 * The array of filter callbacks.
	 *
	 * @var array
	 */
	protected $filters = [];

	/**
	 * The array of reject callbacks.
	 *
	 * @var array
	 */
	protected $rejects = [];

	/**
	 * The array of callbacks to be run before the event is started.
	 *
	 * @var array
	 */
	protected $before_callbacks = [];

	/**
	 * The array of callbacks to be run after the event is finished.
	 *
	 * @var array
	 */
	protected $after_callbacks = [];

	/**
	 * The human readable description of the event.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The exit status code of the command.
	 * 0 for success and 1 for failure.
	 *
	 * @var int|null
	 */
	public $exit_code;

	/**
	 * Exception thrown for the command.
	 *
	 * @var \Throwable
	 */
	public $exception;

	/**
	 * Create a new event instance.
	 *
	 * @param \Closure|string    $callback Event callback.
	 * @param array              $parameters Event parameters..
	 * @param \DateTimeZone|null $timezone Event timezone.
	 */
	public function __construct( $callback, array $parameters = [], $timezone = null ) {
		$this->callback   = $callback;
		$this->parameters = $parameters;
		$this->timezone   = $timezone;
	}

	/**
	 * Run the given event, assumed to be a closure or callable callback.
	 *
	 * @param Container $container
	 */
	public function run( Container $container ) {
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
	 * @param  \Illuminate\Contracts\Container\Container $container
	 * @return void
	 */
	public function call_before_callbacks( Container $container ) {
		foreach ( $this->before_callbacks as $callback ) {
			$container->call( $callback );
		}
	}

	/**
	 * Call all of the "after" callbacks for the event.
	 *
	 * @param  \Illuminate\Contracts\Container\Container $container
	 * @return void
	 */
	public function call_after_callbacks( Container $container ) {
		foreach ( $this->after_callbacks as $callback ) {
			$container->call( $callback );
		}
	}
	/**
	 * Determine if the given event should run based on the Cron expression.
	 *
	 * @param Application $app
	 * @return bool
	 */
	public function is_due( Application $app ) {
		return $this->expression_passes() &&
			$this->runs_in_environment( $app->environment() );
	}

	/**
	 * Determine if the Cron expression passes.
	 *
	 * @return bool
	 */
	protected function expression_passes(): bool {
		$date = Carbon::now();

		if ( $this->timezone ) {
			$date->setTimezone( $this->timezone );
		}


		return CronExpression::factory( $this->expression )->isDue( $date->toDateTimeString() );
	}

	/**
	 * Determine if the event runs in the given environment.
	 *
	 * @param string $environment Environment to check against.
	 * @return bool
	 */
	public function runs_in_environment( $environment ): bool {
		return empty( $this->environments ) || in_array( $environment, $this->environments );
	}

	/**
	 * Determine if the filters pass for the event.
	 *
	 * @param Application $app Application instance.
	 * @return bool
	 */
	public function filters_pass( Application $app ): bool {
		foreach ( $this->filters as $callback ) {
			if ( ! $app->call( $callback ) ) {
				return false;
			}
		}

		foreach ( $this->rejects as $callback ) {
			if ( $app->call( $callback ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Register a callback to ping a given URL before the job runs.
	 *
	 * @param  string $url URL to ping.
	 * @return static
	 */
	public function pingBefore( $url ) {
		return $this->before( $this->pingCallback( $url ) );
	}

	/**
	 * Register a callback to ping a given URL before the job runs if the given condition is true.
	 *
	 * @param  bool   $value Value to compare.
	 * @param  string $url URL to ping.
	 * @return static
	 */
	public function pingBeforeIf( $value, $url ) {
		return $value ? $this->pingBefore( $url ) : $this;
	}

	/**
	 * Register a callback to ping a given URL after the job runs.
	 *
	 * @param string $url URL to ping.
	 * @return static
	 */
	public function thenPing( $url ) {
		return $this->then( $this->pingCallback( $url ) );
	}

	/**
	 * Register a callback to ping a given URL after the job runs if the given condition is true.
	 *
	 * @param  bool   $value Value to compare.
	 * @param  string $url URL to ping.
	 * @return static
	 */
	public function thenPingIf( $value, $url ) {
		return $value ? $this->thenPing( $url ) : $this;
	}

	/**
	 * Register a callback to ping a given URL if the operation succeeds.
	 *
	 * @param string $url URL to ping.
	 * @return static
	 */
	public function pingOnSuccess( $url ) {
		return $this->onSuccess( $this->pingCallback( $url ) );
	}

	/**
	 * Register a callback to ping a given URL if the operation fails.
	 *
	 * @param  string $url
	 * @return static
	 */
	public function pingOnFailure( $url ) {
		return $this->onFailure( $this->pingCallback( $url ) );
	}

	/**
	 * Get the callback that pings the given URL.
	 *
	 * @param string $url URL to ping.
	 * @return \Closure
	 */
	protected function pingCallback( $url ) {
		return function ( Container $container, HttpClient $http ) use ( $url ) {
			try {
				$http->request( 'GET', $url );
			} catch ( ClientExceptionInterface | TransferException $e ) {
				$container->make( Handler::class )->report( $e );
			}
		};
	}

	/**
	 * Limit the environments the command should run in.
	 *
	 * @param  array|mixed ...$environments Environments to run on.
	 * @return static
	 */
	public function environments( ...$environments ) {
		$this->environments = $environments;
		return $this;
	}

	/**
	 * Register a callback to further filter the schedule.
	 *
	 * @param \Closure|bool $callback Callback to be invoked.
	 * @return static
	 */
	public function when( $callback ) {
		$this->filters[] = is_callable( $callback ) ? $callback : function () use ( $callback ) {
			return $callback;
		};

		return $this;
	}

	/**
	 * Register a callback to further filter the schedule.
	 *
	 * @param \Closure|bool $callback Callback to be invoked.
	 * @return static
	 */
	public function skip( $callback ) {
		$this->rejects[] = is_callable( $callback ) ? $callback : function () use ( $callback ) {
			return $callback;
		};

		return $this;
	}

	/**
	 * Register a callback to be called before the operation.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 * @return static
	 */
	public function before( Closure $callback ) {
		$this->before_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Register a callback to be called after the operation.
	 *
	 * @param \Closure $callback  Callback to be invoked.
	 * @return static
	 */
	public function after( Closure $callback ) {
		return $this->then( $callback );
	}

	/**
	 * Register a callback to be called after the operation.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 * @return static
	 */
	public function then( Closure $callback ) {
		$this->after_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Register a callback to be called if the operation succeeds.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 * @return static
	 */
	public function onSuccess( Closure $callback ) {
		return $this->then(
			function ( Container $container ) use ( $callback ) {
				if ( 0 === $this->exit_code ) {
					$container->call( $callback, [ $this ] );
				}
			}
		);
	}

	/**
	 * Register a callback to be called if the operation fails.
	 *
	 * @param \Closure $callback Callback to be invoked.
	 * @return static
	 */
	public function onFailure( Closure $callback ) {
		return $this->then(
			function ( Container $container ) use ( $callback ) {
				if ( 0 !== $this->exit_code ) {
					$container->call( $callback, [ $this ] );
				}
			}
		);
	}

	/**
	 * Get the Cron expression for the event.
	 *
	 * @return string
	 */
	public function get_expression() {
		return $this->expression;
	}
}
