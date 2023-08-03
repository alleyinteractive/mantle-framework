<?php
/**
 * Log_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Log;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Application;
use Mantle\Contracts\Events\Dispatcher;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NewRelicHandler;
use Monolog\Handler\SlackWebhookHandler;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function Mantle\Support\Helpers\collect;

/**
 * Log Handler
 *
 * @phpstan-type Level Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY
 */
class Log_Manager implements LoggerInterface {
	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Dispatcher instance.
	 *
	 * @var Dispatcher
	 */
	protected ?Dispatcher $dispatcher;

	/**
	 * Default logger instance for the application.
	 *
	 * @var Logger|null
	 */
	protected ?Logger $drive;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 * @param Dispatcher  $dispatcher Event dispatcher.
	 */
	public function __construct( Application $app, Dispatcher $dispatcher = null ) {
		$this->app        = $app;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Write to a specific channel.
	 *
	 * @param array|string $channels Channel(s) to log to.
	 * @return Logger
	 */
	public function channel( $channels ): Logger {
		$handlers = collect( (array) $channels )
			->map( [ $this, 'get_channel_handler' ] )
			->filter()
			->to_array();

		return ( new Logger( 'Mantle', $handlers ) )->set_dispatcher( $this->dispatcher );
	}

	/**
	 * Get a Log Handler for a Channel.
	 *
	 * @param string $channel Channel name.
	 * @return HandlerInterface|null
	 *
	 * @throws InvalidArgumentException Thrown on invalid configuration.
	 * @throws Throwable Thrown on error getting the logging handler for a channel.
	 */
	public function get_channel_handler( string $channel ): ?HandlerInterface {
		if ( empty( $channel ) ) {
			return null;
		}

		$config = $this->app['config']->get( 'logging.channels.' . $channel );

		if ( empty( $config['driver'] ) ) {
			throw new InvalidArgumentException( "Channel '{$channel}' missing configuration." );
		}

		$method = "create_{$config['driver']}_handler";

		if ( ! method_exists( $this, $method ) ) {
			throw new InvalidArgumentException( "Driver '{$config['driver']}' is not supported." );
		}

		try {
			$handler = $this->$method( $config );
		} catch ( Throwable $e ) {
			// Throw the exception if there was an error getting the handler in debug mode.
			if ( config( 'app.debug' ) ) {
				throw $e;
			}

			// Fail silently.
			return null;
		}

		return $handler;
	}

	/**
	 * Create a stack handler that combines multiple channels into a single handler.
	 *
	 * @param array $config Configuration.
	 * @return GroupHandler
	 * @throws InvalidArgumentException Thrown on invalid configuration.
	 */
	protected function create_stack_handler( array $config ) {
		if ( empty( $config['channels'] ) ) {
			throw new InvalidArgumentException( 'Stack channel called without any child channels.' );
		}

		$handlers = array_map( [ $this, 'get_channel_handler' ], $config['channels'] );
		return new GroupHandler( $handlers );
	}

	/**
	 * Create an AI Logger Handler
	 *
	 * @link https://github.com/alleyinteractive/logger/
	 *
	 * @param array $config Configuration.
	 * @return \AI_Logger\Handler\Post_Handler
	 */
	protected function create_ai_logger_handler( array $config ): \AI_Logger\Handler\Post_Handler {
		return new \AI_Logger\Handler\Post_Handler( $this->level( $config ) );
	}

	/**
	 * Create a New Relic Handler
	 *
	 * @param array $config Configuration.
	 * @return NewRelicHandler
	 */
	protected function create_new_relic_handler( array $config ): NewRelicHandler {
		return new NewRelicHandler( $this->level( $config ) );
	}

	/**
	 * Create a Slack handler.
	 *
	 * @param array $config Handler configuration.
	 * @return SlackWebhookHandler
	 */
	protected function create_slack_handler( array $config ): SlackWebhookHandler {
		return new SlackWebhookHandler(
			$config['url'],
			$config['channel'] ?? null,
			$config['username'] ?? 'Mantle',
			$config['attachment'] ?? true,
			$config['emoji'] ?? ':boom:',
			$config['short'] ?? false,
			$config['context'] ?? true,
			$this->level( $config ),
			$config['bubble'] ?? true,
			$config['exclude_fields'] ?? []
		);
	}

	/**
	 * Create a custom handler.
	 *
	 * @throws InvalidArgumentException Thrown on invalid configuration.
	 *
	 * @param array $config Handler configuration.
	 * @return AbstractHandler
	 */
	protected function create_custom_handler( array $config ): AbstractHandler {
		if ( empty( $config['handler'] ) ) {
			throw new InvalidArgumentException( 'Custom handler missing "handler" attribute.' );
		}

		if ( $config['handler'] instanceof AbstractHandler ) {
			return $config['handler'];
		}

		return new $config['handler']( $this->level( $config ) );
	}

	/**
	 * Create an Error Log Handler.
	 *
	 * @param array $config Handler configuration.
	 * @return ErrorLogHandler
	 */
	protected function create_error_log_handler( array $config ): ErrorLogHandler {
		return new ErrorLogHandler( ErrorLogHandler::OPERATING_SYSTEM, $this->level( $config ) );
	}

	/**
	 * Get the default channel for the application.
	 *
	 * @return string
	 */
	public function get_default_channel(): string {
		return (string) $this->app['config']->get( 'logging.default' );
	}

	/**
	 * Get the default logger instance.
	 *
	 * @return Logger
	 */
	public function driver(): Logger {
		if ( isset( $this->drive ) ) {
			return $this->drive;
		}

		$this->drive = $this->channel( $this->get_default_channel() );
		return $this->drive;
	}

	/**
	 * Parse the string level into a Monolog constant.
	 *
	 * @param  array $config Handler configuration.
	 * @return int
	 *
	 * @phpstan-return Level
	 *
	 * @throws \InvalidArgumentException Thrown for unknown log.
	 */
	protected function level( array $config ):int {
		$level  = strtoupper( $config['level'] ?? 'debug' );
		$levels = Logger::getLevels();

		if ( isset( $levels[ $level ] ) ) {
			return $levels[ $level ];
		}

		throw new InvalidArgumentException( 'Invalid log level.' );
	}

	/**
	 * Magic method to pass to the default log instance.
	 *
	 * @param string $method Method called.
	 * @param array  $args Arguments for the method.
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		return $this->driver()->$method( ...$args );
	}

	/**
	 * System is unusable.
	 *
	 * @param string|\Stringable $message Log message.
	 * @param mixed[]            $context Log context.
	 *
	 * @return void
	 */
	public function emergency( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->emergency( $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string|\Stringable $message Log message.
	 * @param mixed[]            $context Log context.
	 *
	 * @return void
	 */
	public function alert( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->alert( $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function critical( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->critical( $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function error( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->error( $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function warning( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->warning( $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function notice( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->notice( $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function info( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->info( $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function debug( string|\Stringable $message, array $context = [] ): void {
		$this->driver()->debug( $message, $context );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed   $level Log level.
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 *
	 * @throws \Psr\Log\InvalidArgumentException Thrown on invalid arguments.
	 */
	public function log( $level, string|\Stringable $message, array $context = [] ): void {
		$this->driver()->$level( $message, $context );
	}

	/**
	 * Register a new callback handler for when a log event is triggered.
	 *
	 * @param Closure $callback
	 * @return void
	 * @throws RuntimeException Thrown on missing dispatcher.
	 */
	public function listen( Closure $callback ) {
		if ( ! isset( $this->dispatcher ) ) {
			throw new RuntimeException( 'Event dispatcher not set.' );
		}

		$this->dispatcher->listen( Events\Message_Logged::class, $callback );
	}
}
