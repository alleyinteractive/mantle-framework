<?php
/**
 * Logging Configuration
 *
 * @package Mantle
 */

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;

return [

	/*
	|--------------------------------------------------------------------------
	| Default Log Channel
	|--------------------------------------------------------------------------
	|
	| The default log channel that is used when calling the `Log` class.
	|
	*/
	'default'  => environment( 'LOG_CHANNEL', 'stack' ),

	/*
	|--------------------------------------------------------------------------
	| Log Channel Configuration
	|--------------------------------------------------------------------------
	|
	| Provides configuration for various log channels. Supported drivers are 'new_relic',
	| 'ai_logger', 'slack', 'error_log', and 'custom'.
	|
	*/
	'channels' => [
		'stack'     => [
			'driver'   => 'stack',
			'channels' => [ 'single' ],
			'level'    => environment( 'LOG_LEVEL', 'debug' ),
		],

		'error_log' => [
			'driver' => 'error_log',
			'level'  => environment( 'LOG_LEVEL', 'debug' ),
		],

		'single'    => [
			'driver'       => 'custom',
			'level'        => environment( 'LOG_LEVEL', 'debug' ),
			'handler'      => StreamHandler::class,
			'handler_with' => [
				'stream' => storage_path( 'logs/mantle.log' ),
			],
		],

		'daily'     => [
			'driver' => 'daily',
			'level'  => environment( 'LOG_LEVEL', 'debug' ),
			'days'   => environment( 'LOG_DAILY_DAYS', 14 ),
			'path'   => storage_path( 'logs/mantle.log' ),
		],

		'new_relic' => [
			'driver' => 'new_relic',
			'level'  => environment( 'LOG_LEVEL', 'debug' ),
		],

		/**
		 * Log to the Alley Logger Package
		 *
		 * @link https://github.com/alleyinteractive/logger/
		 */
		'logger'    => [
			'driver' => 'ai_logger',
			'level'  => 'info',
		],

		/**
		 * Log to a Slack Webhook
		 *
		 * @link https://api.slack.com/messaging/webhooks#create_a_webhook
		 */
		'slack'     => [
			'driver'   => 'slack',
			'level'    => environment( 'LOG_LEVEL', 'debug' ),
			'url'      => environment( 'SLACK_URL', '' ),
			'username' => environment( 'SLACK_USERNAME', 'Mantle Log' ),
			'emoji'    => ':boom:',
		],

		'null'      => [
			'driver'  => 'monolog',
			'handler' => NullHandler::class,
		],

		'stderr'    => [
			'driver'       => 'monolog',
			'level'        => environment( 'LOG_LEVEL', 'debug' ),
			'handler'      => StreamHandler::class,
			'handler_with' => [
				'stream' => 'php://stderr',
			],
		],

		/*
		|--------------------------------------------------------------------------
		| Custom Log Channel
		|--------------------------------------------------------------------------
		|
		| Supports passing to a specific handler by class name or Monolog Handler
		| instance via the 'handler' attribute.
		|
		*/
		'custom'    => [
			'driver'  => 'custom',
			'handler' => 'Example\Class\Name',
			'level'   => environment( 'LOG_LEVEL', 'debug' ),
		],
	],
];
