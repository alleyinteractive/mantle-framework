<?php
/**
 * Authorize class file.
 *
 * @package Mantle
 */

namespace Mantle\Auth\Middleware;

use Closure;
use Mantle\Auth\Authentication_Error;
use Mantle\Framework\Http\Request;

/**
 * Authorize Middleware
 *
 * Supports general authorization checks as well as specific capabilities.
 */
class Authorize {
	/**
	 * Handle the request.
	 *
	 * @param Request $request Request object.
	 * @param Closure $next Callback to proceed.
	 * @param string  $ability Abilities to check against, optional.
	 *
	 * @throws Authentication_Error Thrown on invalid access.
	 */
	public function handle( Request $request, Closure $next, string $ability = '' ) {
		if ( ! \is_user_logged_in() ) {
			throw new Authentication_Error( 403, static::get_unauthenticated_error_message() );
		}

		if ( $ability ) {
			foreach ( explode( ',', $ability ) as $cap ) {
				if ( ! \current_user_can( $cap ) ) {
					throw new Authentication_Error( 403, static::get_invalid_access_error_message() );
				}
			}
		}

		return $next( $request );
	}

	/**
	 * Get the error message for users who are not logged in.
	 *
	 * @return string
	 */
	public static function get_unauthenticated_error_message(): string {
		return (string) \apply_filters( 'mantle_auth_not_logged_in_error', __( 'You are not authenticated.', 'mantle' ) );
	}

	/**
	 * Get the error message for users who are not able to access the requested resources.
	 *
	 * @return string
	 */
	public static function get_invalid_access_error_message(): string {
		return (string) \apply_filters( 'mantle_auth_not_logged_in_error', __( 'You do not have sufficient permissions.', 'mantle' ) );
	}
}
