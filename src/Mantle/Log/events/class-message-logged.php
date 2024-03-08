<?php
/**
 * Message_Logged class file.
 *
 * @package Mantle
 */

namespace Mantle\Log\Events;

/**
 * Message Logged Event
 */
class Message_Logged {
	/**
	 * The log "level".
	 *
	 * @var string
	 */
	public $level;

	/**
	 * The log message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * The log context.
	 *
	 * @var array
	 */
	public $context;

	/**
	 * Constructor.
	 *
	 * @param string $level Log level.
	 * @param string $message Message.
	 * @param array  $context Log context.
	 */
	public function __construct( string $level, string $message, array $context = [] ) {
		$this->level   = $level;
		$this->message = $message;
		$this->context = $context;
	}
}
