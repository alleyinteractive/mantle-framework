<?php
/**
 * Logger class file.
 *
 * @package Mantle
 */

namespace Mantle\Log;

use DateTimeImmutable;
use Mantle\Contracts\Events\Dispatcher;
use Monolog\Logger as MonologLogger;

/**
 * Logger Class.
 *
 * Wraps Monolog to provide event firing.
 */
class Logger extends MonologLogger {
	/**
	 * Dispatcher instance.
	 *
	 * @var Dispatcher|null
	 */
	protected $dispatcher;

	/**
	 * Set the dispatcher instance.
	 *
	 * @param Dispatcher $dispatcher Dispatcher instance.
	 * @return static
	 */
	public function set_dispatcher( Dispatcher $dispatcher = null ) {
		$this->dispatcher = $dispatcher;
		return $this;
	}

	/**
	 * Adds a log record.
	 *
	 * @param int               $level    The logging level.
	 * @param string            $message  The log message.
	 * @param mixed[]           $context  The log context.
	 * @param DateTimeImmutable $datetime Optional log date to log into the past or future.
	 * @return bool    Whether the record has been processed
	 */
	public function addRecord( int $level, string $message, array $context = [], DateTimeImmutable $datetime = null ): bool {
		$this->fire_log_event( $this->getLevelName( $level ), $message, $context );

		return parent::addRecord( $level, $message, $context, $datetime );
	}

	/**
	 * Fire the log event.
	 *
	 * @param string $level Log level.
	 * @param string $message Message.
	 * @param array  $context Log context.
	 * @return void
	 */
	protected function fire_log_event( string $level, string $message, array $context = [] ): void {
		if ( isset( $this->dispatcher ) ) {
			$this->dispatcher->dispatch( new Events\Message_Logged( $level, $message, $context ) );
		}
	}
}
