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
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
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
	 * Default logger instance for the application.
	 */
	protected ?Logger $drive = null;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 * @param Dispatcher  $dispatcher Event dispatcher.
	 */
	public function __construct( protected Application $app, protected ?Dispatcher $dispatcher = null ) {
	}

	/**
	 * Write to a specific channel.
	 *
	 * @param string[]|string $channels Channel(s) to log to.
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
	 *
	 * @throws InvalidArgumentException Thrown on invalid configuration.
	 * @throws Throwable Thrown on error getting the logging handler for a channel.
	 */
	public function get_channel_handler( string $channel ): ?HandlerInterface {
		if ( empty( $channel ) ) {
			return null;
		}

		$config = $this->app['config']->get( "logging.channels.{$channel}" );

		if ( empty( $config['driver'] ) ) {
			throw new InvalidArgumentException( "Channel '{$channel}' missing configuration." );
		}

		$method = "create_{$config['driver']}_handler";

		// Legacy name for the monolog driver.
		if ( 'custom' === $config['driver'] ) {
			$method = 'create_monolog_handler';
		}

		if ( ! method_exists( $this, $method ) ) {
			throw new InvalidArgumentException( "Driver '{$config['driver']}' is not supported." );
		}

		try {
			$handler = $this->$method( $config );
		} catch ( Throwable $e ) {
			if ( config( 'app.debug' ) ) {
				throw $e;
			}

			return null;
		}

		return $handler;
	}

	/**
	 * Create a stack handler that combines multiple channels into a single handler.
	 *
	 * @param array<mixed> $config Configuration.
	 * @throws InvalidArgumentException Thrown on invalid configuration.
	 */
	protected function create_stack_handler( array $config ): \Monolog\Handler\GroupHandler {
		if ( empty( $config['channels'] ) ) {
			throw new InvalidArgumentException( 'Stack channel called without any child channels.' );
		}

		return new GroupHandler( array_map( [ $this, 'get_channel_handler' ], $config['channels'] ) );
	}

	/**
	 * Create an AI Logger Handler
	 *
	 * @link https://github.com/alleyinteractive/logger/
	 *
	 * @param array<mixed> $config Configuration.
	 */
	protected function create_ai_logger_handler( array $config ): \AI_Logger\Handler\Post_Handler {
		return new \AI_Logger\Handler\Post_Handler( $this->level( $config ) );
	}

	/**
	 * Create a New Relic Handler
	 *
	 * @param array<mixed> $config Configuration.
	 */
	protected function create_new_relic_handler( array $config ): NewRelicHandler {
		return new NewRelicHandler( $this->level( $config ) );
	}

	/**
	 * Create a Slack handler.
	 *
	 * @param array<mixed> $config Handler configuration.
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
	 * @param array<mixed> $config Handler configuration.
	 */
	protected function create_monolog_handler( array $config ): HandlerInterface {
		if ( empty( $config['handler'] ) ) {
			throw new InvalidArgumentException( 'Custom handler missing "handler" attribute.' );
		}

		if ( $config['handler'] instanceof HandlerInterface ) {
			return $config['handler'];
		}

		$arguments = array_merge( $config['handler_with'] ?? [], [
			'level' => $this->level( $config ),
		] );

		return new $config['handler']( ...$arguments );
	}

	/**
	 * Create an Error Log Handler.
	 *
	 * @param array<mixed> $config Handler configuration.
	 */
	protected function create_error_log_handler( array $config ): ErrorLogHandler {
		return new ErrorLogHandler( ErrorLogHandler::OPERATING_SYSTEM, $this->level( $config ), expandNewlines: true );
	}

	/**
	 * Create a daily rotating file handler.
	 *
	 * @param array<mixed> $config Handler configuration.
	 */
	protected function create_daily_handler( array $config ): HandlerInterface {
		return new RotatingFileHandler(
			$config['path'],
			$config['days'] ?? 14,
			$this->level( $config ),
			$config['bubble'] ?? true,
			$config['file_permission'] ?? null,
			$config['use_locking'] ?? false,
		);
	}

	/**
	 * Get the default channel for the application.
	 */
	public function get_default_channel(): string {
		return (string) $this->app['config']->get( 'logging.default' );
	}

	/**
	 * Get the default logger instance.
	 */
	public function driver(): Logger {
		if ( $this->drive instanceof \Mantle\Log\Logger ) {
			return $this->drive;
		}

		$this->drive = $this->channel( $this->get_default_channel() );
		return $this->drive;
	}

	/**
	 * Parse the string level into a Monolog constant.
	 *
	 * @param  array<mixed> $config Handler configuration.
	 *
	 * @phpstan-return Level
	 * @throws \InvalidArgumentException Thrown for unknown log.
	 */
	protected function level( array $config ): int {
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
	 * @param string       $method Method called.
	 * @param array<mixed> $args Arguments for the method.
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
	 */
	public function emergency( $message, array $context = [] ): void {
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
	 */
	public function alert( $message, array $context = [] ): void {
		$this->driver()->alert( $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 */
	public function critical( $message, array $context = [] ): void {
		$this->driver()->critical( $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 */
	public function error( $message, array $context = [] ): void {
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
	 */
	public function warning( $message, array $context = [] ): void {
		$this->driver()->warning( $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 */
	public function notice( $message, array $context = [] ): void {
		$this->driver()->notice( $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 */
	public function info( $message, array $context = [] ): void {
		$this->driver()->info( $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 */
	public function debug( $message, array $context = [] ): void {
		$this->driver()->debug( $message, $context );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed   $level Log level.
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @throws \Psr\Log\InvalidArgumentException Thrown on invalid arguments.
	 */
	public function log( $level, $message, array $context = [] ): void {
		$this->driver()->$level( $message, $context );
	}

	/**
	 * Register a new callback handler for when a log event is triggered.
	 *
	 * @param Closure $callback
	 * @throws RuntimeException Thrown on missing dispatcher.
	 */
	public function listen( Closure $callback ): void {
		if ( ! isset( $this->dispatcher ) ) {
			throw new RuntimeException( 'Event dispatcher not set.' );
		}

		$this->dispatcher->listen( Events\Message_Logged::class, $callback );
	}
}
