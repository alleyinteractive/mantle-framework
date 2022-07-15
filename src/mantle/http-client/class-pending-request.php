<?php
namespace Mantle\Http_Client;

class Pending_Request {
	protected $method;
	protected $args = [];

	public function __call( string $method, array $args ) {
		$this->method = $method;
		$this->args = $args;

		return $this;
	}
}
