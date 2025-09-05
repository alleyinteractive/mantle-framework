<?php
/**
 * Stray_Request_Exception
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Exceptions;

use Mantle\Http_Client\Request;
use RuntimeException;

/**
 * Stray Request Exception
 *
 * Thrown when a stray external HTTP request is made and is actively being prevented.
 */
class Stray_Request_Exception extends RuntimeException {
	/**
	 * Constructor.
	 *
	 * @param string  $message Exception message.
	 * @param string  $url URL being requested.
	 * @param Request $request Request object.
	 */
	public function __construct( string $message, public readonly string $url, public readonly Request $request ) {
		parent::__construct( $message );
	}
}
