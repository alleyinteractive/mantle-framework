<?php
/**
 * Error_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Support\Service_Provider;

/**
 * Error Service Provider
 */
class Error_Service_Provider extends Service_Provider implements Isolated_Service_Provider {
	/**
	 * Error Handler
	 *
	 * @var \Whoops\Run
	 */
	protected $handler;

	/**
	 * Register any application services.
	 */
	public function register(): void {
		if ( ! $this->app->make( 'config' )->get( 'app.debug', false ) ) {
			return;
		}

		$this->handler = new \Whoops\Run();


		if ( $this->app->is_running_in_console() ) {
			$this->handler->pushHandler( new \Whoops\Handler\PlainTextHandler() );
		} elseif ( \Whoops\Util\Misc::isAjaxRequest() ) {
			$this->handler->pushHandler( new \Whoops\Handler\JsonResponseHandler() );
		} else {
			$this->handler->pushHandler( new \Whoops\Handler\PrettyPageHandler() );
		}

		$this->handler->register();
	}
}
