<?php
/**
 * Authorize class file.
 *
 * @package Mantle
 */

namespace Mantle\Auth\Middleware;

use Closure;
use Mantle\Auth\Authentication_Error;
use Mantle\Http\Request;

use function Mantle\Support\Helpers\mixed;

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
	 * @param string  ...$abilities Abilities to check against, optional.
	 *
	 * @throws Authentication_Error Thrown on invalid access.
	 */
	public function handle( Request $request, Closure $next, string ...$abilities ): mixed {
		if ( ! \is_user_logged_in() ) {
			throw new Authentication_Error( 403, static::get_unauthenticated_error_message() );
		}

		foreach ( $abilities as $ability ) {
			foreach ( explode( ',', $ability ) as $cap ) {
				$cap = trim( $cap );

				if ( '' !== $cap && ! \current_user_can( $cap ) ) {
					throw new Authentication_Error( 403, static::get_invalid_access_error_message() );
				}
			}
		}

		return $next( $request );
	}

	/**
	 * Get the error message for users who are not logged in.
	 */
	public static function get_unauthenticated_error_message(): string {
		return mixed( \apply_filters( 'mantle_auth_not_logged_in_error', __( 'You are not authenticated.', 'mantle' ) ) )->string();
	}

	/**
	 * Get the error message for users who are not able to access the requested resources.
	 */
	public static function get_invalid_access_error_message(): string {
		return mixed( \apply_filters( 'mantle_auth_insufficient_permissions_error', __( 'You do not have sufficient permissions.', 'mantle' ) ) )->string();
	}
}
