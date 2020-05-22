<?php
/**
 * Router interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Http\Routing;

/**
 * Router Contract
 */
interface Router {
	public function get( string $uri, $action );
	public function post( string $uri, $action );
	public function put( string $uri, $action );
	public function delete( string $uri, $action );
	public function patch( string $uri, $action );
	public function options( string $uri, $action );
}
