<?php
namespace Mantle\Contracts\Http\Routing;

use Mantle\Http\Routing\Route;

interface Route_Registrar {
	// public function attribute( string $key, mixed $value ): static;

	public function register_route( string|array $method, string $uri, \Closure|array|string $action = null ): Route;

	public function attribute( string $key, mixed $value ): static;
}
