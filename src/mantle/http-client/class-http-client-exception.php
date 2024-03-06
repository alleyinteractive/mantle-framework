<?php
/**
 * Http_Client_Exception class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use Exception;

/**
 * HTTP Client exception.
 */
class Http_Client_Exception extends Exception {
	/**
	 * Error response.
	 */
	public Response $response;

	/**
	 * Constructor.
	 *
	 * @param Response $response Http Response.
	 */
	public function __construct( Response $response ) {
		$this->response = $response;
	}
}
