<?php
/**
 * Mantle Featherkit Loader
 *
 * @package Mantle
 */

use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Contracts;
use Mantle\Featherkit;
use Mantle\Http\Request;

/**
 * Default configuration for Featherkit
 */
const DEFAULT_FEATHERKIT_CONFIG = [
	'app'        => [
		'debug'     => false,
		'providers' => [
			// Framework Providers (mirrors config/app.php).
			Mantle\Filesystem\Filesystem_Service_Provider::class,
			Mantle\Database\Factory_Service_Provider::class,
			Mantle\Framework\Providers\Error_Service_Provider::class,
			Mantle\Database\Model_Service_Provider::class,
			Mantle\Queue\Queue_Service_Provider::class,
			Mantle\Query_Monitor\Query_Monitor_Service_Provider::class,
			Mantle\New_Relic\New_Relic_Service_Provider::class,
			Mantle\Database\Pagination\Paginator_Service_Provider::class,
			Mantle\Cache\Cache_Service_Provider::class,

			// Featherkit Providers.
			Mantle\Application\App_Service_Provider::class,
			Mantle\Assets\Asset_Service_Provider::class,
			Mantle\Framework\Providers\Event_Service_Provider::class,
			Mantle\Framework\Providers\Route_Service_Provider::class,
		],
	],
	'cache'      => [
		'default' => 'wordpress',
		'stores'  => [
			'wordpress' => [
				'driver' => 'wordpress',
			],
			'array'     => [
				'driver' => 'array',
			],
		],
	],
	'filesystem' => [
		'default' => 'local',
		'disks'   => [
			'local' => [
				'driver' => 'local',
			],
		],
	],
	'logging'    => [
		'default'  => 'stack',
		'channels' => [
			'stack'     => [
				'driver'   => 'stack',
				'channels' => [ 'error_log' ],
			],

			'error_log' => [
				'driver' => 'error_log',
				'level'  => 'error',
			],
		],
	],
	'queue'      => [
		'default'    => 'wordpress',
		'batch_size' => 100,
		'wordpress'  => [
			'delay' => 0,
		],
	],
	'view'       => [],
];

static $featherkit = null;

if ( ! function_exists( 'featherkit' ) ) {
	/**
	 * Load a pre-configured Mantle Application.
	 */
	function featherkit(
		array $config = [],
		string $base_path = null,
		string $root_url = null,
	): Application {
		global $featherkit;

		if ( $featherkit ) {
			return $featherkit;
		}

		$featherkit = new Application( $base_path ?? ABSPATH, $root_url ?? home_url() );

		// Register the main contracts for the application.
		$featherkit->singleton( Contracts\Http\Kernel::class, Featherkit\Http\Kernel::class );
		$featherkit->singleton( Contracts\Exceptions\Handler::class, Featherkit\Exceptions\Handler::class );

		// Setup the application's configuration.
		$featherkit->instance( 'config', new Repository( array_merge( DEFAULT_FEATHERKIT_CONFIG, $config ) ) );

		// Fire off the HTTP kernel.
		$kernel = $featherkit->make( Contracts\Http\Kernel::class );
		$kernel->handle( Request::capture() );

		return $featherkit;
	}
}
