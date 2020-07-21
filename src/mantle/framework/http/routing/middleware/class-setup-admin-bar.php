<?php
/**
 * Setup_Admin_Bar class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing\Middleware;

use Closure;
use Mantle\Framework\Http\Request;

/**
 * Setup the admin bar for Mantle routing.
 */
class Setup_Admin_Bar {
	/**
	 * Store if the admin bar was already setup.
	 *
	 * @var bool
	 */
	protected static $did_setup = false;

	/**
	 * Handle an incoming request and setup the admin bar.
	 *
	 * @param Request  $request Request instance.
	 * @param \Closure $next Callback for the middleware.
	 * @return mixed
	 */
	public function handle( Request $request, Closure $next ) {
		if ( ! static::$did_setup && ! \did_action( 'template_redirect' ) ) {
			do_action( 'wp' );
			\_wp_admin_bar_init();
			static::$did_setup = true;
		}

		return $next( $request );
	}
}
