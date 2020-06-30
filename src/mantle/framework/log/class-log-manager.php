<?php
/**
 * Log_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Log;

use InvalidArgumentException;
use Mantle\Framework\Contracts\Application;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\Handler;
use Monolog\Handler\NewRelicHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Logger;
use Throwable;

use function Mantle\Framework\Helpers\collect;

/**
 * Log Handler
 */
class Log_Manager {
	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Default logger instance for the application.
	 *
	 * @var Logger
	 */
	protected $default_logger;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
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

		return new Logger( 'Mantle', $handlers );
	}

	/**
	 * Get a Log Handler for a Channel.
	 *
	 * @param string $channel Channel name.
	 * @return Handler|null
	 *
	 * @throws InvalidArgumentException Thrown on invalid configuration.
	 * @throws Throwable Thrown on error getting the logging handler for a channel.
	 */
	public function get_channel_handler( string $channel ): ?Handler {
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
	public function get_default_logger(): Logger {
		if ( isset( $this->default_logger ) ) {
			return $this->default_logger;
		}

		$this->default_logger = $this->channel( $this->get_default_channel() );
		return $this->default_logger;
	}

	/**
	 * Parse the string level into a Monolog constant.
	 *
	 * @param  array $config Handler configuration.
	 * @return int
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
		return $this->get_default_logger()->$method( ...$args );
	}
}
