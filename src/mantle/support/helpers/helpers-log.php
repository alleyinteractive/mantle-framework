<?php
/**
 * This file contains assorted log helpers.
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

/**
 * Write some information to the log.
 *
 * @param  string $message Log message.
 * @param  array  $context Log context.
 */
function info( string $message, array $context = [] ) {
	app( 'log' )->info( $message, $context );
}

/**
 * Log a debug message.
 *
 * @param  string|null $message Log message.
 * @param  array       $context Log context.
 * @return \Mantle\Log\Log_Manager|null
 */
function logger( string $message = null, array $context = [] ) {
	if ( is_null( $message ) ) {
		return app( 'log' );
	}

	return app( 'log' )->debug( $message, $context );
}
