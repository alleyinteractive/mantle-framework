<?php
/**
 * Error_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Support\Providers;

use Mantle\Framework\Service_Provider;

/**
 * Error Service Provider
 */
class Error_Service_Provider extends Service_Provider {
	/**
	 * Error Handler
	 *
	 * @var \Whoops\Run
	 */
	protected $handler;

	/**
	 * Register any application services.
	 */
	public function register() {
		if ( ! $this->app->config->get( 'app.debug', false ) ) {
			return;
		}

		$this->handler = new \Whoops\Run();

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			$this->handler->pushHandler( new \Whoops\Handler\PlainTextHandler() );
		} elseif ( \Whoops\Util\Misc::isAjaxRequest() ) {
			$this->handler->pushHandler( new \Whoops\Handler\JsonResponseHandler() );
		} else {
			$this->handler->pushHandler( new \Whoops\Handler\PrettyPageHandler() );
		}

		$this->handler->register();
	}
}
