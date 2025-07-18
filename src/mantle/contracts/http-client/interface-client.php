<?php
namespace Mantle\Contracts\Http_Client;

use Mantle\Http_Client\Pending_Client_Request;

interface Client {
	public function get( string $url, array|string|null $query = null );
	public function head( string $url, array|string|null $query = null );
	public function post( string $url, ?array $data = null );
	public function patch( string $url, ?array $data = null );
	public function put( string $url, ?array $data = null );
	public function delete( string $url, ?array $data = null );

	// public function send( Pending_Client_Request $request, array $options = [] );
}
