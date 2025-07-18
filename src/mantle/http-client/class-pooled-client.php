<?php
namespace Mantle\Http_Client;

use Mantle\Contracts\Http_Client\Client as Contract;

class Pooled_Client implements Contract {
	public function __construct( protected Pending_Request $request = new Pending_Request() ) {}


	public function get( string $url, array|string|null $query = null ): static {}
	public function head( string $url, array|string|null $query = null ): static {}
	public function post( string $url, ?array $data = null ): static {}
	public function patch( string $url, ?array $data = null ): static {}
	public function put( string $url, ?array $data = null ): static {}
	public function delete( string $url, ?array $data = null ): static {}
}
