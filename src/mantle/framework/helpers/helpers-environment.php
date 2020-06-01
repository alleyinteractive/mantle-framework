<?php
/**
 * Environment helpers.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Helpers;

/**
 * Check if we are on a hosted environment
 *
 * @return bool
 */
function is_hosted_env(): bool {
	return (
		( defined( 'WPCOM_IS_VIP_ENV' ) && \WPCOM_IS_VIP_ENV )
		|| ( defined( 'PANTHEON_ENVIRONMENT' ) && \PANTHEON_ENVIRONMENT )
	);
}

/**
 * Check if the current environment is a local developer environment.
 *
 * @return bool
 */
function is_local_env(): bool {
	return ! is_hosted_env();
}
