<?php
/**
 * Application Configuration
 *
 * @package Mantle
 */

return [

	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| Enable detailed error messages with stack traces will be shown on every error
	| that occurs within your application. If disabled, a simple generic error page
	| is shown.
	|
	*/
	'debug'       => environment( 'APP_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG ),

	/*
	|--------------------------------------------------------------------------
	| Service Providers
	|--------------------------------------------------------------------------
	|
	| Providers listed here will be autoloaded for every request on the application.
	|
	 */
	'providers'   => [
		// Framework Providers.
		Mantle\Filesystem\Filesystem_Service_Provider::class,
		Mantle\Database\Factory_Service_Provider::class,
		Mantle\Database\Model_Service_Provider::class,
		Mantle\Queue\Queue_Service_Provider::class,
		Mantle\Query_Monitor\Query_Monitor_Service_Provider::class,
		Mantle\New_Relic\New_Relic_Service_Provider::class,
		Mantle\Database\Pagination\Paginator_Service_Provider::class,
		Mantle\Cache\Cache_Service_Provider::class,

		/*
		|--------------------------------------------------------------------------
		| Application Providers
		|--------------------------------------------------------------------------
		|
		| These are the providers that power your application. The above ones are
		| core to Mantle. The following are designed to be implemented and extended
		| by your application. Mantle will use the framework's version unless
		| otherwise specified. You can export the framework's version by running the
		| following command and switching out the provider's class name:
		|
		|   wp mantle vendor:publish --tags=application-structure --force
		|
		*/
		Mantle\Application\App_Service_Provider::class,
		Mantle\Assets\Asset_Service_Provider::class,
		Mantle\Events\Event_Service_Provider::class,
	],

	/*
	|--------------------------------------------------------------------------
	| Application Aliases
	|--------------------------------------------------------------------------
	|
	| These are aliases that will be available to the entire application without
	| the need use the proper namespace.
	|
	 */
	'aliases'     => [
		'App'     => Mantle\Facade\App::class,
		'Cache'   => Mantle\Facade\Cache::class,
		'Config'  => Mantle\Facade\Config::class,
		'Event'   => Mantle\Facade\Event::class,
		'Log'     => Mantle\Facade\Log::class,
		'Post'    => Mantle\Database\Model\Post::class,
		'Queue'   => Mantle\Facade\Queue::class,
		'Request' => Mantle\Facade\Request::class,
		'Route'   => Mantle\Facade\Route::class,
		'Storage' => Mantle\Facade\Storage::class,
		'Term'    => Mantle\Database\Model\Term::class,
		'View'    => Mantle\Facade\View::class,
	],

	/*
	|--------------------------------------------------------------------------
	| Application Namespace
	|--------------------------------------------------------------------------
	|
	| Used to provide a configurable namespace for class generation.
	|
	*/
	'namespace'   => environment( 'APP_NAMESPACE', 'App' ),

	/*
	|--------------------------------------------------------------------------
	| Application Text Domain
	|--------------------------------------------------------------------------
	|
	| The text domain used by Mantle when scaffolding files with translatable
	| strings.
	|
	*/
	'i18n_domain' => environment( 'APP_I18N_DOMAIN', 'mantle' ),
];
