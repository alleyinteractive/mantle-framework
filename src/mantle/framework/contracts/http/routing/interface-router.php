<?php
namespace Mantle\Framework\Contracts\Http\Routing;

interface Router {
	public function get( string $uri, $action, string $name = '' );
	public function post( string $uri, $action, string $name = '' );
	public function put( string $uri, $action, string $name = '' );
	public function delete( string $uri, $action, string $name = '' );
	public function patch( string $uri, $action, string $name = '' );
	public function options( string $uri, $action, string $name = '' );
}
