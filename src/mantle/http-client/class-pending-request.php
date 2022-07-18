<?php
namespace Mantle\Http_Client;

/**
 * Pending Request to be made with the Http Client.
 */
class Pending_Request {
	protected $method;
	protected $args = [];

	public function __call( string $method, array $args ) {
		$this->method = $method;
		$this->args = $args;

		return $this;
	}
}
